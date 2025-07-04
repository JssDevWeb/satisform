# 📚 Índice Visual de Documentación

<div align="center">

![Sistema de Encuestas](https://img.shields.io/badge/Sistema-Encuestas_Acad%C3%A9micas-blue?style=for-the-badge)
![Documentación](https://img.shields.io/badge/Documentación-Completa-green?style=for-the-badge)

</div>

## 📑 Mapa de Documentación

```mermaid
graph TD
    A[README.md] --> B[MANUAL_USUARIO.md]
    A --> C[docs/CENTRO_DOCUMENTACION.md]
    
    subgraph "Documentación Principal"
        B[MANUAL_USUARIO.md]
        C[docs/CENTRO_DOCUMENTACION.md]
        D[docs/ORGANIZACION_DOCUMENTACION.md]
    end
    
    C --> E[docs/admin/]
    C --> F[docs/development/]
    C --> G[docs/technical/]
    
    subgraph "Administración"
        E --> E1[MANUAL_PANEL_ADMINISTRATIVO.md]
        E --> E2[GUIA_REPORTES_PDF_ANALISIS.md]
    end
    
    subgraph "Desarrollo"
        F --> F1[ACTUALIZACION_ESTRUCTURA.md]
        F --> F2[SOLUCION_PDF.md]
        F --> F3[RESUMEN_LIMPIEZA_FECHAS.md]
    end
    
    subgraph "Técnica"
        G --> G1[DOCUMENTACION_PDF_SISTEMA.md]
        G --> G2[IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md]
        G --> G3[MEJORAS_PREGUNTAS_CRITICAS.md]
        G --> G4[IMPLEMENTACION_COMENTARIOS_FINALIZADA.md]
    end
    
    C --> H[docs/images/]
    H --> H1[Imágenes de Documentación]
```

## 🗂️ Estructura de Carpetas

```
/
├── README.md                           # Información general del proyecto
├── MANUAL_USUARIO.md                   # Manual principal para usuarios
├── CHANGELOG.md                        # Historial de cambios y versiones
├── CONTRIBUTING.md                     # Guía para contribuidores
├── LICENSE                             # Licencia del proyecto
│
└── /docs                               # Documentación centralizada
    ├── CENTRO_DOCUMENTACION.md         # Índice centralizado de documentación
    ├── ORGANIZACION_DOCUMENTACION.md   # Estándares de documentación
    ├── INDICE_VISUAL.md                # Este archivo (mapa visual)
    │
    ├── /admin                          # Documentación para administradores
    │   ├── MANUAL_PANEL_ADMINISTRATIVO.md
    │   └── GUIA_REPORTES_PDF_ANALISIS.md
    │
    ├── /development                    # Documentación de desarrollo
    │   ├── ACTUALIZACION_ESTRUCTURA.md
    │   ├── SOLUCION_PDF.md
    │   └── RESUMEN_LIMPIEZA_FECHAS.md
    │
    ├── /technical                      # Documentación técnica específica
    │   ├── DOCUMENTACION_PDF_SISTEMA.md
    │   ├── IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md
    │   ├── MEJORAS_PREGUNTAS_CRITICAS.md
    │   └── IMPLEMENTACION_COMENTARIOS_FINALIZADA.md
    │
    └── /images                         # Recursos visuales
        ├── README.md
        └── [imágenes utilizadas en documentación]
```

## 📝 Convenciones de Documentación

| Tipo de Documento | Ubicación | Convención de Nombre |
|------------------|-----------|----------------------|
| Manuales Principales | Raíz | `MANUAL_*.md` |
| Guías de Administrador | `/docs/admin/` | `GUIA_*.md` o `MANUAL_*.md` |
| Documentación Técnica | `/docs/technical/` | `*_FINALIZADA.md` o `DOCUMENTACION_*.md` |
| Documentación de Desarrollo | `/docs/development/` | `*_ESTRUCTURA.md` o `SOLUCION_*.md` |
| Índices y Organización | `/docs/` | `*_DOCUMENTACION.md` |

---

<div align="center">

*Este índice visual forma parte de la iniciativa de mejora de documentación del Sistema de Encuestas Académicas.*

</div>
