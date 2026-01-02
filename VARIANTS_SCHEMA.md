# SCHEMA DE VARIANTES

## Estructura de config.json con variantes

```json
{
  "id": "pudu",
  "name": "Pudú",
  "scientificName": "Pudu puda",
  "description": "Descripción general del animal",
  "thumbnail": "thumbnail.jpg",
  "icon": "🦌",
  "silhouette": "silueta_pudu.svg",
  "arMode": "hybrid",

  "activeVariant": "default",

  "variants": {
    "default": {
      "model": {
        "glb": "pudu.glb",
        "usdz": "pudu.usdz",
        "scale": "1 1 1",
        "position": "0 0 0",
        "rotation": "0 0 0"
      },
      "audio": {
        "enabled": true,
        "file": "sound.mp3"
      },
      "marker": {
        "enabled": true,
        "type": "mind",
        "file": "pudu.mind"
      }
    },
    "navidad": {
      "model": {
        "glb": "pudu_navidad.glb",
        "usdz": "pudu_navidad.usdz",
        "scale": "1.2 1.2 1.2",
        "position": "0 0 0",
        "rotation": "0 0 0"
      },
      "audio": {
        "enabled": true,
        "file": "sound_navidad.mp3"
      },
      "marker": {
        "enabled": true,
        "type": "mind",
        "file": "pudu_navidad.mind"
      }
    },
    "verano": {
      "model": {
        "glb": "pudu_verano.glb",
        "scale": "1 1 1"
      }
    }
  },

  "gps": {
    "enabled": false,
    "latitude": -39.8196,
    "longitude": -73.2452,
    "radius": 50
  },

  "info": {
    "habitat": "Bosques templados lluviosos",
    "diet": "Herbívoro",
    "status": "Casi amenazado",
    "wikipedia": "https://es.wikipedia.org/wiki/Pudu"
  },

  "image": "imagen_pudu.png"
}
```

## Prioridad de carga de variantes

1. **Instancia específica** (`instances.json` → `variant`)
2. **Lugar** (`places.json` → `activeVariants[animalId]`)
3. **Animal global** (`config.json` → `activeVariant`)
4. **Default** (variante "default")

## Compatibilidad con configs antiguos

Configs sin propiedad `variants` son tratados como variante única "default".

## Archivos de variantes

Cada variante puede tener archivos separados:
- `pudu.glb` (default)
- `pudu_navidad.glb` (navidad)
- `pudu_verano.glb` (verano)

O compartir archivos base y solo modificar parámetros (scale, rotation, etc.)
