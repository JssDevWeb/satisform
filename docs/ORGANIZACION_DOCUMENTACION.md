# 📂 Organización de la Documentación - Sistema de Encuestas Académicas

<div align="center">

![Organización Documental](https://img.shields.io/badge/Organización-Documentación-blue?style=for-the-badge)
![Última Actualización](https://img.shields.io/badge/Actualización-Julio_2025-green?style=for-the-badge)

</div>

## 🎯 Objetivo

Este documento establece las mejores prácticas para la organización de archivos de documentación (.md) en el proyecto de Sistema de Encuestas Académicas, asegurando un acceso intuitivo y eficiente a toda la información relevante.

## 📑 Estructura Recomendada

### 1. Archivos en la Raíz del Proyecto

Los siguientes archivos deben permanecer en la raíz por ser documentación esencial de acceso inmediato:

- `README.md` - Información general del proyecto y punto de entrada
- `CHANGELOG.md` - Historial de cambios y versiones
- `CONTRIBUTING.md` - Guía para contribuidores
- `LICENSE` - Licencia del proyecto
- `MANUAL_USUARIO.md` - Guía principal para usuarios finales

### 2. Documentación Técnica en `/docs`

Toda la documentación técnica, manuales específicos y guías detalladas deben ubicarse en la carpeta `/docs`:

```
/docs
├── admin/
│   ├── MANUAL_PANEL_ADMINISTRATIVO.md
│   ├── GUIA_REPORTES_PDF_ANALISIS.md
│   └── [otros manuales de administración]
├── development/
│   ├── ACTUALIZACION_ESTRUCTURA.md
│   ├── SOLUCION_PDF.md
│   └── [otra documentación técnica]
├── technical/
│   ├── IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md
│   ├── MEJORAS_PREGUNTAS_CRITICAS.md
│   └── [documentación específica de componentes]
├── images/
│   ├── README.md
│   └── [imágenes utilizadas en documentación]
└── CENTRO_DOCUMENTACION.md
```

### 3. Enlaces desde README.md

El archivo `README.md` principal debe contener enlaces a toda la documentación relevante, tanto en la raíz como en la carpeta `/docs`, funcionando como un índice general del proyecto.

## 🔄 Plan de Migración

1. **Fase 1**: Crear la estructura de carpetas en `/docs`
2. **Fase 2**: Mover los archivos .md técnicos y específicos a sus respectivas carpetas
3. **Fase 3**: Actualizar todos los enlaces internos entre documentos
4. **Fase 4**: Actualizar el `README.md` con la nueva estructura

## 🛠️ Consideraciones Técnicas

- Mantener rutas relativas en los enlaces entre documentos
- Centralizar todas las imágenes en `/docs/images/`
- Mantener un formato consistente en todos los archivos .md
- Incluir siempre una sección de navegación al inicio de cada documento

## 📌 Convenciones de Nomenclatura

- Nombres de archivos en MAYÚSCULAS con guiones bajos: `NOMBRE_DEL_DOCUMENTO.md`
- Nombres descriptivos que indiquen claramente el contenido
- Evitar nombres genéricos como "info.md" o "doc.md"

---

<div align="center">

*Esta guía fue creada como parte de la iniciativa de mejora de documentación del Sistema de Encuestas Académicas.*

</div>
