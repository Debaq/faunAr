# FaunAR - Sistema Portal WebAR

## REGLAS IMPORTANTES

- NUNCA dar resúmenes de lo hecho al usuario
- NUNCA modificar archivos .md existentes
- NUNCA crear nuevos archivos .md

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
    └── [nombre-modelo]/
        ├── config.json          # Configuración del modelo
        ├── [nombre].glb         # Modelo 3D
        ├── [nombre].usdz        # Modelo 3D iOS (opcional)
        ├── thumbnail.jpg        # Miniatura (opcional)
        ├── sound.mp3            # Sonido (opcional)
        └── [nombre].patt/.mind  # Patrón AR
```
