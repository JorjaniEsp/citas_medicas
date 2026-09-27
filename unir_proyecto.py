import os
import stat
import tempfile

ARCHIVO_SALIDA = 'proyecto_limpio.txt'

# 1. CARPETAS QUE SÍ QUEREMOS LEER (todo lo demás se ignora, incluyendo 'mysql')
CARPETAS_PERMITIDAS = ['Business', 'Data', 'Presentation', 'db_dump']

# 2. ARCHIVOS EN LA RAÍZ QUE SÍ NOS INTERESAN
ARCHIVOS_RAIZ = ['docker-compose.yml', 'db_password.txt']

# 3. SUBCARPETAS QUE SE PODAN DENTRO DE LAS PERMITIDAS (no se entra en ellas)
CARPETAS_EXCLUIDAS = {'vendor', 'node_modules', '.git', 'mysql', '__pycache__'}

# 4. ARCHIVOS CON CREDENCIALES: no se copian a menos que cambies esto a True
INCLUIR_SECRETOS = False
ARCHIVOS_SECRETOS = {'.env', 'db_password.txt'}

# 5. LÍMITE DE TAMAÑO POR ARCHIVO (evita volcar dumps gigantes o binarios)
TAMANO_MAXIMO = 1 * 1024 * 1024  # 1 MB

RAIZ_PROYECTO = os.path.realpath(os.getcwd())


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
    if not INCLUIR_SECRETOS and nombre in ARCHIVOS_SECRETOS:
        return True
    return False


def agregar_archivo(ruta_completa, salida):
    if debe_omitirse(os.path.basename(ruta_completa)) or not es_seguro(ruta_completa):
        return False
    try:
        # Solo lectura, en binario, para poder detectar archivos no-texto
        with open(ruta_completa, 'rb') as f:
            datos = f.read()
        if b'\x00' in datos:
            return False  # binario
        contenido = datos.decode('utf-8')
    except (OSError, UnicodeDecodeError):
        return False

    ruta_relativa = os.path.relpath(ruta_completa, RAIZ_PROYECTO)
    salida.write(f"\n{'='*60}\n")
    salida.write(f"ARCHIVO: {ruta_relativa}\n")
    salida.write(f"{'='*60}\n\n")
    salida.write(contenido)
    salida.write("\n")
    return True


def generar_txt_limpio():
    procesados = 0
    destino = os.path.join(RAIZ_PROYECTO, ARCHIVO_SALIDA)

    # Se escribe primero en un temporal; si algo falla, el archivo anterior queda intacto
    fd, temporal = tempfile.mkstemp(prefix='.proyecto_', suffix='.tmp', dir=RAIZ_PROYECTO)
    try:
        with os.fdopen(fd, 'w', encoding='utf-8', newline='\n') as salida:

            # Primero los archivos de la raíz (como el docker-compose)
            for archivo in ARCHIVOS_RAIZ:
                if os.path.isfile(archivo) and agregar_archivo(archivo, salida):
                    procesados += 1

            # Luego SOLO las carpetas permitidas, podando las excluidas
            for carpeta in CARPETAS_PERMITIDAS:
                if not os.path.isdir(carpeta) or os.path.islink(carpeta):
                    continue
                for raiz, dirs, archivos in os.walk(carpeta, followlinks=False):
                    dirs[:] = sorted(
                        d for d in dirs
                        if d not in CARPETAS_EXCLUIDAS
                        and not os.path.islink(os.path.join(raiz, d))
                    )
                    for archivo in sorted(archivos):
                        if agregar_archivo(os.path.join(raiz, archivo), salida):
                            procesados += 1

        # Permisos: lectura/escritura solo para tu usuario (en Windows solo aplica "solo lectura")
        os.chmod(temporal, stat.S_IRUSR | stat.S_IWUSR)
        os.replace(temporal, destino)
    except Exception:
        if os.path.exists(temporal):
            os.remove(temporal)
        raise

    print(f"Se generó '{ARCHIVO_SALIDA}' con {procesados} archivos (sin vendor ni mysql).")


if __name__ == '__main__':
    generar_txt_limpio()