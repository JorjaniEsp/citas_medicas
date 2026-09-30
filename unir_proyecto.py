import os
import stat
import tempfile

ARCHIVO_SALIDA = 'proyecto_limpio.txt'

# La raíz es la carpeta donde vive este script, no desde donde se ejecuta
RAIZ_PROYECTO = os.path.realpath(os.path.dirname(os.path.abspath(__file__)))

# 1. CARPETAS QUE SÍ QUEREMOS LEER (todo lo demás se ignora, incluyendo 'mysql')
CARPETAS_PERMITIDAS = ['Business', 'Data', 'citas_medicas', 'Presentacion', 'db_dump']

# 2. ARCHIVOS EN LA RAÍZ QUE SÍ NOS INTERESAN
ARCHIVOS_RAIZ = ['docker-compose.yml', '.dockerignore']

# 3. SUBCARPETAS QUE SE PODAN DENTRO DE LAS PERMITIDAS (no se entra en ellas)
CARPETAS_EXCLUIDAS = {
    'vendor', 'node_modules', '.git', 'mysql', '__pycache__',
    '.angular', 'dist', '.vscode',
}

# 4. ARCHIVOS GENERADOS AUTOMÁTICAMENTE: mucho ruido y ninguna información útil
ARCHIVOS_EXCLUIDOS = {'package-lock.json', 'composer.lock'}
EXTENSIONES_EXCLUIDAS = {'.map', '.log'}

# 5. ARCHIVOS CON CREDENCIALES: no se copian a menos que cambies esto a True
INCLUIR_SECRETOS = False
ARCHIVOS_SECRETOS = {'.env', 'db_password.txt'}

# 6. LÍMITE DE TAMAÑO POR ARCHIVO (evita volcar dumps gigantes o binarios)
TAMANO_MAXIMO = 1 * 1024 * 1024  # 1 MB


def es_seguro(ruta):
    """Solo archivos regulares, sin enlaces simbólicos y dentro del proyecto."""
    if os.path.islink(ruta):
        return False
    real = os.path.realpath(ruta)
    if os.path.commonpath([real, RAIZ_PROYECTO]) != RAIZ_PROYECTO:
        return False
    try:
        info = os.stat(real, follow_symlinks=False)
    except OSError:
        return False
    return stat.S_ISREG(info.st_mode) and info.st_size <= TAMANO_MAXIMO


def debe_omitirse(nombre):
    if nombre == ARCHIVO_SALIDA:
        return True
    if nombre in ARCHIVOS_EXCLUIDOS:
        return True
    if os.path.splitext(nombre)[1].lower() in EXTENSIONES_EXCLUIDAS:
        return True
    if not INCLUIR_SECRETOS and nombre in ARCHIVOS_SECRETOS:
        return True
    return False


def agregar_archivo(ruta_completa, salida):
    if debe_omitirse(os.path.basename(ruta_completa)) or not es_seguro(ruta_completa):
        return None
    try:
        # Solo lectura, en binario, para poder detectar archivos no-texto
        with open(ruta_completa, 'rb') as f:
            datos = f.read()
        if b'\x00' in datos:
            return None  # binario
        # utf-8-sig quita el BOM que a veces agregan editores de Windows
        contenido = datos.decode('utf-8-sig')
    except (OSError, UnicodeDecodeError):
        return None

    # Unifica saltos de línea: todo el txt queda con LF
    contenido = contenido.replace('\r\n', '\n')

    # Rutas con "/" aunque se ejecute en Windows
    ruta_relativa = os.path.relpath(ruta_completa, RAIZ_PROYECTO).replace(os.sep, '/')
    salida.write(f"\n{'='*60}\n")
    salida.write(f"ARCHIVO: {ruta_relativa}\n")
    salida.write(f"{'='*60}\n\n")
    salida.write(contenido)
    salida.write("\n")
    return ruta_relativa


def generar_txt_limpio():
    incluidos = []
    carpetas_faltantes = []
    destino = os.path.join(RAIZ_PROYECTO, ARCHIVO_SALIDA)

    # Se escribe primero en un temporal; si algo falla, el archivo anterior queda intacto
    fd, temporal = tempfile.mkstemp(prefix='.proyecto_', suffix='.tmp', dir=RAIZ_PROYECTO)
    try:
        with os.fdopen(fd, 'w', encoding='utf-8', newline='\n') as salida:

            # Primero los archivos de la raíz (como el docker-compose)
            for archivo in ARCHIVOS_RAIZ:
                ruta = os.path.join(RAIZ_PROYECTO, archivo)
                if os.path.isfile(ruta):
                    resultado = agregar_archivo(ruta, salida)
                    if resultado:
                        incluidos.append(resultado)

            # Luego SOLO las carpetas permitidas, podando las excluidas
            for carpeta in CARPETAS_PERMITIDAS:
                ruta_carpeta = os.path.join(RAIZ_PROYECTO, carpeta)
                if not os.path.isdir(ruta_carpeta) or os.path.islink(ruta_carpeta):
                    carpetas_faltantes.append(carpeta)
                    continue
                for raiz, dirs, archivos in os.walk(ruta_carpeta, followlinks=False):
                    dirs[:] = sorted(
                        d for d in dirs
                        if d not in CARPETAS_EXCLUIDAS
                        and not os.path.islink(os.path.join(raiz, d))
                    )
                    for archivo in sorted(archivos):
                        resultado = agregar_archivo(os.path.join(raiz, archivo), salida)
                        if resultado:
                            incluidos.append(resultado)

        # Permisos: lectura/escritura solo para tu usuario (en Windows solo aplica "solo lectura")
        os.chmod(temporal, stat.S_IRUSR | stat.S_IWUSR)
        os.replace(temporal, destino)
    except Exception:
        if os.path.exists(temporal):
            os.remove(temporal)
        raise

    print(f"Se generó '{ARCHIVO_SALIDA}' con {len(incluidos)} archivos.")
    print(f"Carpetas excluidas: {', '.join(sorted(CARPETAS_EXCLUIDAS))}")
    for carpeta in carpetas_faltantes:
        print(f"AVISO: no se encontró la carpeta '{carpeta}' (¿la renombraron?)")


if __name__ == '__main__':
    generar_txt_limpio()