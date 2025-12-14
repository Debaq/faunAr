# FaunAR

Sistema modular de realidad aumentada web para experiencias de fauna.

## Características

- Portal web para listar proyectos AR disponibles
- **Generación automática de códigos QR** para acceso directo
- Soporte para GPS y marcadores de imagen
- Sistema modular de configuración
- Compatibilidad con modelos GLB/GLTF
- Integración de audio opcional
- Información educativa sobre cada especie

## Estructura del Proyecto

```
faunar/
├── index.html                    # Portal principal
├── viewer.html                   # Visor AR universal
├── css/
│   ├── portal.css               # Estilos del portal
│   └── viewer.css               # Estilos del visor
├── js/
│   ├── portal.js                # Lógica del portal
│   ├── ar-engine.js             # Motor AR (GPS + marcadores)
│   └── config-loader.js         # Cargador de configuraciones
└── models/                       # Subcarpetas de modelos
    └── chucao/
        ├── config.json          # Configuración del modelo
        ├── model.glb            # Modelo 3D
        ├── marker.mind          # Marcador AR (opcional)
        └── thumbnail.jpg        # Miniatura (opcional)
```

## Agregar nuevo modelo

1. Crear carpeta en `models/nombre-animal/`
2. Agregar archivos:
   - `config.json` (obligatorio)
   - `model.glb` (obligatorio)
   - `model.usdz` (opcional, para iOS nativo)
   - `thumbnail.jpg` (opcional, recomendado)
   - `sound.mp3` (opcional)
   - `marker.mind` o `marker.patt` (según configuración)

3. Editar `js/portal.js` línea 6 y agregar el nombre de la carpeta al array `modelFolders`:
   ```javascript
   const modelFolders = ['chucao', 'nuevo-animal'];
   ```

## Configuración

### Ejemplo de config.json

```json
{
  "id": "chucao",
  "name": "Chucao",
  "scientificName": "Scelorchilus rubecula",
  "description": "El chucao es un ave endémica de los bosques templados del sur de Chile y Argentina.",
  "thumbnail": "thumbnail.jpg",
  "arMode": "hybrid",
  "gps": {
    "enabled": true,
    "latitude": -41.4693,
    "longitude": -72.9424,
    "radius": 50
  },
  "marker": {
    "enabled": true,
    "type": "mind",
    "file": "marker.mind"
  },
  "model": {
    "glb": "model.glb",
    "usdz": "model.usdz",
    "scale": "1 1 1",
    "position": "0 0 0",
    "rotation": "0 0 0"
  },
  "audio": {
    "enabled": false,
    "file": "sound.mp3"
  },
  "info": {
    "habitat": "Bosques templados húmedos",
    "diet": "Insectívoro",
    "status": "Preocupación menor"
  }
}
```

### Modos AR disponibles

- `"gps"`: Solo geolocalización
- `"marker"`: Solo marcadores de imagen
- `"hybrid"`: Ambos métodos

### Tipos de marcador

- `"mind"`: Archivos MIND (NFT) para imágenes naturales
- `"pattern"`: Archivos .patt para marcadores tradicionales

## Configuración GPS

En `config.json` usa coordenadas reales del lugar donde quieres que aparezca el modelo:

```json
"gps": {
  "enabled": true,
  "latitude": -41.4693,
  "longitude": -72.9424,
  "radius": 50
}
```

El `radius` define el área en metros donde el modelo será visible.

## Deployment

Subir a cualquier hosting con **HTTPS** (requisito para WebXR):
- Netlify
- Vercel
- GitHub Pages
- Cloudflare Pages

## Tecnologías

- [A-Frame](https://aframe.io/) - Framework WebXR
- [AR.js](https://ar-js-org.github.io/AR.js-Docs/) - Realidad Aumentada Web
- Vanilla JavaScript - Sin dependencias adicionales

## Uso

### Desde el portal web

1. Accede al portal principal (`index.html`)
2. Selecciona una experiencia AR usando el botón "Ver en AR"
3. Permite el acceso a la cámara y GPS (si aplica)
4. Apunta a un marcador o dirígete a la ubicación GPS

### Usando códigos QR

1. En el portal, haz clic en "Ver QR" en cualquier proyecto
2. Escanea el código QR con tu dispositivo móvil
3. Se abrirá directamente la experiencia AR sin pasar por el portal
4. Opcionalmente, descarga el QR para imprimirlo o compartirlo

Los códigos QR generan URLs del tipo:
```
https://tu-dominio.com/viewer.html?model=chucao
```

## Notas

- Se requiere HTTPS para acceder a cámara y GPS
- Los modelos 3D deben estar optimizados para web (< 5MB recomendado)
- Para marcadores MIND, genera los archivos usando [AR.js Marker Training](https://carnaux.github.io/NFT-Marker-Creator/)
