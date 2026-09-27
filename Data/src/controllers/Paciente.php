<?php
namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Models\Paciente as PacienteModel;
use Illuminate\Database\QueryException;
use App\Models\User;

class Paciente {

    public function __construct(private ContainerInterface $container){ }

    public function create(Request $request, Response $response, array $args){
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $errors = [];
        $cedula = trim( (string) ($data['cedula'] ?? '') );
        $fullName = trim((string) ($data['nombre_completo'] ?? ''));
        $fechaNacimiento = trim((string) ($data['fecha_nacimiento'] ?? ''));
        $phone = isset($data['telefono']) ? trim((string) $data['telefono']) : null;
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if( $cedula === '' || mb_strlen($cedula) > 11){
            $errors['cedula'] = 'Es obligatoria y debe tener máximo 11 caracteres.';
        }

        if ($fullName === '' || mb_strlen($fullName) > 150) {
            $errors['nombre_completo'] = 'Es obligatorio y debe tener máximo 150 caracteres.';
        }

        if($fechaNacimiento == '' || mb_strlen($fechaNacimiento) > 10){
            $errors['fecha_nacimiento'] = 'Es obligatorio y debe tener el formato AAAA-MM-DD';
        }

        if ($username === '' || mb_strlen($username) > 50) {
            $errors['username'] = 'Es obligatorio y debe tener máximo 50 caracteres.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Debe tener al menos 8 caracteres.';
        }

        if ($phone !== null && $phone === '') {
            $phone = null;
        } elseif ($phone !== null && mb_strlen($phone) > 20) {
            $errors['telefono'] = 'Debe tener máximo 20 caracteres.';
        }

        if($errors !== []){
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            $eloquent = $this->container->get('eloquent');
            $paciente = $eloquent->connection()->transaction(
                static function () use ($cedula, $fullName, $fechaNacimiento, $phone, $username, $password): PacienteModel {
                    $roleId = User::query()
                        ->from('roles')
                        ->where('nombre', 'Paciente')
                        ->value('id');

                    if ($roleId === null) {
                        throw new \RuntimeException('El rol Paciente no está configurado.');
                    }

                    $user = User::create([
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'rol_id' => $roleId,
                        'activo' => true,
                    ]);

                    return PacienteModel::create([
                        'usuario_id'       => $user->id,
                        'cedula'           => $cedula,
                        'nombre_completo'  => $fullName,
                        'fecha_nacimiento' => $fechaNacimiento,
                        'telefono'         => $phone
                    ]);
                }
            );
        } catch (QueryException $exception){
            if ((int) ($exception->errorInfo[1] ?? 0 ) === 1062) {

                $message = (string) ( $exception->errorInfo[2] ?? '');

                if (str_contains($message, 'username')) {
                    return $this->json($response, ['error' => 'El nombre de usuario ya está registrado.'], 409);
                }

                return $this->json($response, ['error' => 'La cedula ya está registrada.'], 409);
            }

            throw $exception;
        }
        return $this->json($response,$data, 201);
    }

    public function read(Request $request, Response $response, array $args){
        $this->container->get('eloquent');

        if(isset($args['id'])){
            $paciente = PacienteModel::query()
            ->select( 'id', 'usuario_id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono')
            ->find($args['id']);

            return $this->json($response, ['data0' => $paciente], 200);
        }

        $pacientes = PacienteModel::query()
            ->select( 'id', 'usuario_id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono')
            ->get()
            ->toArray();
        return $this->json($response, ['data0' => $pacientes], 200);
    }

    public function filter(Request $request, Response $response, array $args) : Response {
        $this->container->get('eloquent');

        $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit  = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

        if ($offset === false || $offset < 0 || $limit == false || $limit < 1 || $limit > 100){
            return $this->json($response, [
                'error' => 'El offset debe ser mayor o igual a 0 y el limite debe estar entre 1 y 100'
            ], 422);
        }

        $params = $request->getQueryParams();
        $name   = trim( (String) ($params['nombre'] ?? $params['nombre_completo'] ?? '') );
        $cedula = trim( (String) ($params['cedula'] ?? '') );

        $query = PacienteModel::query()
            ->select(['id', 'nombre_completo', 'cedula', 'fecha_nacimiento', 'telefono']);

        if( $name !== '' ){
            $query->where('nombre_completo', 'like', "%{$name}%");
        }

        if( $cedula !== '' ){
            $query->where('cedula', 'like', "%{$cedula}%");
        }

        $total     = (clone $query)->count('id');
        $pacientes = $query
            ->orderBy('nombre_completo', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return $this->json($response, [
            'data'       => $pacientes,
            'pagination' => [
                'offset' => $offset,
                'limit'  => $limit
            ]
        ], 200);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $paciente = PacienteModel::find($args['id']);

            if ($paciente === null) {
                return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
            }

            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];

            if (isset($data['nombre_completo'])) {
                $fullName = trim((string) $data['nombre_completo']);
                if ($fullName === '' || mb_strlen($fullName) > 150) {
                    return $this->json($response, ['error' => 'Nombre completo inválido.'], 422);
                }
                $paciente->nombre_completo = $fullName;
            }

            if (isset($data['telefono'])) {
                $phone = trim((string) $data['telefono']);

                if ($phone !== null && mb_strlen($phone) > 20) {
                    return $this->json($response, ['error' => 'Debe tener máximo 20 caracteres.'], 422);
                }
                $paciente->telefono = $phone;
            }

            $paciente->save();

            return $this->json($response, ['data' => $paciente->toArray()], 200);
        }
        return $this->json($response, ['error' => 'Datos incompletos'], 400);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');

        if (isset($args['id'])) {
            $paciente = PacienteModel::find($args['id']);

            if ($paciente === null) {
                return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
            }

            // verificar si el médico a eliminar tengas citas
            $citasCount = $eloquent->table('citas')
                ->where('paciente_id', $paciente->id)
                ->count();

            if ($citasCount > 0){
                return $this->json($response, ['error' => 'No se puede eliminar al paciente, por qué tiene citas asignadas'], 409);
            }


            $eloquent->connection()->transaction( static function() use ($paciente) : void {
                // obtener el id del medico
                $usuarioId = $paciente->usuario_id;

                $paciente->delete();
                
                if ($usuarioId !== null) {
                    $usuario = User::find($usuarioId);
                    if ($usuario !== null){
                        $usuario->delete();
                    }
                }
            });


            return $this->json($response, ['message' => 'Paciente eliminado exitosamente'], 200);
        } // cierre de si existe médivo
    }

    private function json(Response $response, array $payload, int $status) : Response {
        $response->getBody()->write(json_encode($payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json ; charset=utf-8')
            ->withStatus($status);
    }
}