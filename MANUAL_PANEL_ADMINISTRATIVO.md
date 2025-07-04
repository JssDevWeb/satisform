# 🔧 Manual del Panel Administrativo - Sistema de Encuestas Académicas

<div align="center">

![Panel Administrativo](https://img.shields.io/badge/Panel-Administrativo-blue?style=for-the-badge)
![Versión](https://img.shields.io/badge/Versión-1.0-green?style=for-the-badge)
![Fecha](https://img.shields.io/badge/Fecha-Julio_2025-orange?style=for-the-badge)

*Guía detallada para administradores del Sistema de Encuestas Académicas*

</div>

---

## 📑 Tabla de Contenidos

1. [Introducción](#-introducción)
2. [Acceso al Panel](#-acceso-al-panel)
3. [Dashboard Principal](#-dashboard-principal)
4. [Gestión de Cursos](#-gestión-de-cursos)
5. [Gestión de Profesores](#-gestión-de-profesores)
6. [Gestión de Formularios](#-gestión-de-formularios)
7. [Gestión de Preguntas](#-gestión-de-preguntas)
8. [Reportes y Estadísticas](#-reportes-y-estadísticas)
   - [Reportes Generales](#reportes-generales)
   - [Reportes PDF Avanzados](#reportes-pdf-avanzados)
   - [Análisis de Datos](#análisis-de-datos)
9. [Configuración del Sistema](#-configuración-del-sistema)
10. [Solución de Problemas Comunes](#-solución-de-problemas-comunes)

---

## 📌 Introducción

Este manual está diseñado para administradores del Sistema de Encuestas Académicas y proporciona instrucciones detalladas sobre todas las funcionalidades del panel administrativo. El sistema permite gestionar encuestas, cursos, profesores y generar reportes avanzados para la evaluación académica.

### Requisitos Previos

- Credenciales de administrador válidas
- Navegador web actualizado (Chrome, Firefox, Edge o Safari)
- Resolución de pantalla mínima recomendada: 1280×720

### Convenciones del Manual

- **Negrita**: Elementos de la interfaz, botones o menús
- *Cursiva*: Notas importantes o consejos
- `Código`: Valores específicos o entradas de texto

---

## 🔑 Acceso al Panel

### Paso 1: Acceder a la URL del Panel

Navegue a la URL del sistema y añada `/admin` al final:
```
http://[su-dominio]/formulario/admin/
```

### Paso 2: Iniciar Sesión

1. Ingrese su **nombre de usuario** en el campo correspondiente
2. Ingrese su **contraseña** en el campo correspondiente
3. Haga clic en el botón **Iniciar Sesión**

![Pantalla de Inicio de Sesión](ruta/a/imagen_login.png)

> **Nota**: Si olvidó su contraseña, contacte al administrador del sistema.

### Paso 3: Verificación de Seguridad

En algunos casos, el sistema puede solicitar verificación adicional:

1. Revise su correo electrónico para el código de verificación
2. Ingrese el código en la pantalla de verificación
3. Haga clic en **Verificar**

---

## 📊 Dashboard Principal

El Dashboard es la página principal del panel administrativo y proporciona una visión general del sistema.

### Características Principales

#### Sección Superior: Métricas Clave

- **Total de Encuestas**: Número total de encuestas recibidas
- **Encuestas Hoy**: Encuestas completadas en el día actual
- **Encuestas Semana**: Encuestas completadas en la semana actual
- **Encuestas Mes**: Encuestas completadas en el mes actual
- **Formularios Activos**: Número de formularios disponibles actualmente

#### Sección Central: Gráficos Interactivos

- **Gráfico de Barras**: Muestra encuestas completadas por mes
- **Gráfico de Torta**: Distribución de evaluaciones por curso
- **Gráfico de Línea**: Tendencia de participación en el tiempo

![Dashboard Principal](ruta/a/imagen_dashboard.png)

#### Sección Inferior: Actividad Reciente

- **Últimas Encuestas**: Lista de las encuestas más recientes
- **Comentarios Recientes**: Últimos comentarios cualitativos recibidos
- **Alertas del Sistema**: Notificaciones importantes sobre el funcionamiento

### Personalización del Dashboard

1. Haga clic en el botón **Personalizar** en la esquina superior derecha
2. Active o desactive los widgets según sus necesidades
3. Arrastre y suelte los widgets para reorganizarlos
4. Haga clic en **Guardar Configuración** para aplicar los cambios

### Acciones Rápidas

El Dashboard incluye acciones rápidas para las tareas más comunes:

- **+ Nuevo Curso**: Crear un nuevo curso
- **+ Nuevo Profesor**: Añadir un nuevo profesor
- **+ Nueva Encuesta**: Configurar un nuevo formulario
- **📊 Ver Reportes**: Acceder directamente a los reportes

---

## 📚 Gestión de Cursos

La sección de Cursos permite administrar todos los cursos disponibles en el sistema.

### Visualización de Cursos

Al ingresar a la sección, verá una tabla con todos los cursos registrados, mostrando:

- **ID**: Identificador único del curso
- **Nombre**: Nombre completo del curso
- **Código**: Código académico del curso
- **Créditos**: Número de créditos del curso
- **Estado**: Activo o Inactivo
- **Acciones**: Botones para editar, ver detalles o eliminar

### Creación de un Nuevo Curso

#### Paso 1: Iniciar la Creación

Haga clic en el botón **Nuevo Curso** ubicado en la parte superior derecha.

#### Paso 2: Completar el Formulario

Complete todos los campos requeridos:

- **Nombre del Curso**: Ej. "Cálculo Diferencial" (obligatorio)
- **Código**: Ej. "MAT101" (obligatorio)
- **Créditos**: Ej. "4" (obligatorio)
- **Descripción**: Breve descripción del curso (opcional)
- **Estado**: Active la casilla para que el curso esté disponible

![Formulario de Nuevo Curso](ruta/a/imagen_nuevo_curso.png)

#### Paso 3: Guardar el Curso

Haga clic en **Guardar** para crear el nuevo curso. El sistema mostrará una notificación de confirmación.

### Edición de un Curso

1. Localice el curso en la tabla
2. Haga clic en el botón de edición (icono de lápiz)
3. Modifique los campos necesarios
4. Haga clic en **Actualizar** para guardar los cambios

### Eliminación de un Curso

> **⚠️ Advertencia**: La eliminación de cursos puede afectar a datos históricos y reportes.

1. Localice el curso en la tabla
2. Haga clic en el botón de eliminación (icono de papelera)
3. Confirme la acción en el diálogo de confirmación

### Filtrado y Búsqueda de Cursos

- **Barra de Búsqueda**: Escriba para filtrar por nombre o código
- **Filtros Avanzados**:
  - **Estado**: Filtre por cursos activos o inactivos
  - **Créditos**: Filtre por número de créditos
  - **Departamento**: Filtre por departamento académico

---

## 👨‍🏫 Gestión de Profesores

La sección de Profesores permite administrar todos los profesores registrados en el sistema.

### Visualización de Profesores

La tabla principal muestra:

- **ID**: Identificador único del profesor
- **Nombre**: Nombre completo del profesor
- **Email**: Correo electrónico
- **Departamento**: Departamento académico
- **Estado**: Activo o Inactivo
- **Cursos Asignados**: Número de cursos asignados al profesor
- **Acciones**: Editar, ver detalles o eliminar

### Creación de un Nuevo Profesor

#### Paso 1: Iniciar la Creación

Haga clic en el botón **Nuevo Profesor** ubicado en la parte superior derecha.

#### Paso 2: Información Personal

Complete la información personal del profesor:

- **Nombre Completo**: (obligatorio)
- **Email**: Correo electrónico institucional (obligatorio)
- **Teléfono**: (opcional)
- **Departamento**: Departamento académico (obligatorio)
- **Grado Académico**: Ej. "Doctor", "Magister" (opcional)
- **Especialidad**: Área de especialización (opcional)
- **Estado**: Active la casilla para que el profesor esté disponible

#### Paso 3: Asignación de Cursos

1. En la pestaña **Cursos Asignados**, verá la lista de cursos disponibles
2. Marque las casillas de los cursos que desea asignar al profesor
3. También puede asignar cursos posteriormente desde la sección de detalles

#### Paso 4: Guardar la Información

Haga clic en **Guardar** para crear el nuevo profesor.

### Edición de un Profesor

1. Localice al profesor en la tabla
2. Haga clic en el botón de edición (icono de lápiz)
3. Modifique los campos necesarios
4. Haga clic en **Actualizar** para guardar los cambios

### Ver Evaluaciones de un Profesor

Para ver las evaluaciones recibidas por un profesor específico:

1. Haga clic en **Ver Detalles** junto al nombre del profesor
2. Navegue a la pestaña **Evaluaciones**
3. Seleccione el curso y período para ver las evaluaciones específicas

![Detalles de Profesor](ruta/a/imagen_detalles_profesor.png)

---

## 📋 Gestión de Formularios

La sección de Formularios permite crear y administrar los formularios de encuesta que se mostrarán a los estudiantes.

### Tipos de Formularios

El sistema admite dos tipos de formularios:

- **Formularios de Curso**: Evalúan aspectos generales del curso
- **Formularios de Profesor**: Evalúan aspectos específicos del profesor

### Creación de un Nuevo Formulario

#### Paso 1: Iniciar la Creación

Haga clic en **Nuevo Formulario** en la parte superior derecha.

#### Paso 2: Información Básica

Complete los campos básicos:

- **Nombre del Formulario**: Ej. "Evaluación Final Semestre 2025-1" (obligatorio)
- **Curso**: Seleccione el curso asociado (obligatorio)
- **Descripción**: Breve descripción del propósito (opcional)
- **Estado**: Active la casilla para que el formulario esté disponible

#### Paso 3: Configuración de Preguntas

1. En la pestaña **Preguntas**, verá dos secciones:
   - **Preguntas de Curso**: Para evaluar el curso
   - **Preguntas de Profesor**: Para evaluar a los profesores

2. Para cada sección:
   - Marque las preguntas que desea incluir
   - Puede reorganizar el orden usando las flechas
   - Puede previsualizar la pregunta haciendo clic en el icono de ojo

![Configuración de Preguntas](ruta/a/imagen_configurar_preguntas.png)

#### Paso 4: Opciones Avanzadas

En la pestaña **Opciones Avanzadas**, puede configurar:

- **Fecha de Inicio**: Cuándo estará disponible el formulario
- **Fecha de Fin**: Cuándo dejará de estar disponible
- **Límite de Respuestas**: Número máximo de respuestas
- **Mostrar Progreso**: Si mostrar o no barra de progreso
- **Permitir Guardar y Continuar**: Para completar la encuesta en varias sesiones

#### Paso 5: Guardar el Formulario

Haga clic en **Guardar Formulario** para crear el nuevo formulario.

### Duplicación de Formularios

Para ahorrar tiempo, puede duplicar un formulario existente:

1. Localice el formulario en la tabla
2. Haga clic en el botón de duplicar (icono de copia)
3. Modifique el nombre y otras opciones según sea necesario
4. Haga clic en **Guardar como Nuevo**

### Previsualización de Formularios

Para ver cómo aparecerá el formulario a los estudiantes:

1. Localice el formulario en la tabla
2. Haga clic en el botón de previsualización (icono de ojo)
3. Navegue por las diferentes secciones del formulario

---

## ❓ Gestión de Preguntas

La sección de Preguntas permite crear y administrar el banco de preguntas para los formularios de encuesta.

### Organización de Preguntas

Las preguntas están organizadas en dos categorías principales:

- **Preguntas de Curso**: Para evaluar aspectos del curso
- **Preguntas de Profesor**: Para evaluar al profesor

Cada categoría se muestra en una pestaña separada para facilitar la gestión.

### Creación de Nuevas Preguntas

#### Paso 1: Seleccionar la Categoría

Seleccione la pestaña apropiada según el tipo de pregunta que desea crear:
- **Preguntas de Curso**
- **Preguntas de Profesor**

#### Paso 2: Iniciar la Creación

Haga clic en el botón **Nueva Pregunta** en la parte superior derecha.

#### Paso 3: Configurar la Pregunta

Complete los siguientes campos:

- **Texto de la Pregunta**: La pregunta que verán los estudiantes (obligatorio)
- **Tipo de Respuesta**: Seleccione una de las opciones:
  - **Escala (1-5)**: Para evaluaciones numéricas
  - **Texto Libre**: Para comentarios cualitativos
  - **Sí/No**: Para preguntas binarias
  - **Opción Múltiple**: Para selección entre varias opciones
- **Orden**: Posición de la pregunta en el formulario (obligatorio)
- **Obligatoria**: Marque si la respuesta es obligatoria
- **Estado**: Active la casilla para que la pregunta esté disponible

![Formulario de Nueva Pregunta](ruta/a/imagen_nueva_pregunta.png)

#### Paso 4: Opciones Avanzadas (según el tipo)

Si seleccionó **Opción Múltiple**:
1. Haga clic en **Agregar Opciones**
2. Ingrese cada opción de respuesta
3. Puede reorganizar las opciones con las flechas

#### Paso 5: Guardar la Pregunta

Haga clic en **Guardar Pregunta** para añadirla al banco de preguntas.

### Edición de Preguntas Existentes

1. Localice la pregunta en la tabla
2. Haga clic en el botón de edición (icono de lápiz)
3. Modifique los campos necesarios
4. Haga clic en **Actualizar** para guardar los cambios

### Eliminación de Preguntas

> **⚠️ Advertencia**: Eliminar preguntas puede afectar a formularios existentes y datos históricos.

1. Localice la pregunta en la tabla
2. Haga clic en el botón de eliminación (icono de papelera)
3. Confirme la acción en el diálogo de confirmación

### Importación y Exportación de Preguntas

Para facilitar la gestión a gran escala, puede importar o exportar preguntas:

#### Exportar Preguntas
1. Haga clic en **Exportar Preguntas**
2. Seleccione el formato (CSV o Excel)
3. Seleccione las categorías a exportar
4. Haga clic en **Descargar**

#### Importar Preguntas
1. Haga clic en **Importar Preguntas**
2. Descargue la plantilla si es su primera importación
3. Suba el archivo con el formato correcto
4. Verifique la vista previa y confirme la importación

---

## 📊 Reportes y Estadísticas

La sección de Reportes proporciona herramientas avanzadas para analizar los resultados de las encuestas.

### Reportes Generales

#### Generación de Reportes Básicos

1. En la pestaña **Reportes Generales**:
   - Seleccione el **Curso** de la lista desplegable
   - Seleccione la **Fecha** o período de evaluación
   - Haga clic en **Generar Reporte**

2. El sistema mostrará:
   - **Estadísticas Generales**: Promedio, mediana, moda y desviación estándar
   - **Gráficos de Torta**: Distribución de respuestas por pregunta
   - **Tabla de Resultados**: Detalle numérico de cada pregunta

![Reportes Generales](ruta/a/imagen_reportes_generales.png)

#### Filtrado de Resultados

Utilice los filtros disponibles para afinar su análisis:

- **Por Profesor**: Seleccione uno o varios profesores
- **Por Sección**: Filtre por secciones específicas del curso
- **Por Tipo de Pregunta**: Filtre por categoría de pregunta
- **Por Puntuación**: Filtre por rango de puntuación (ej. 1-2, 4-5)

### Reportes PDF Avanzados

Los reportes PDF ofrecen un análisis profesional y detallado para la toma de decisiones.

#### Generación de PDF Académico

1. Seleccione **Curso** y **Fecha** igual que en los reportes generales
2. Haga clic en **Exportar a PDF**
3. Configure las opciones del reporte:
   - **Incluir Gráficos**: Active para incluir visualizaciones
   - **Incluir Comentarios**: Active para incluir respuestas cualitativas
   - **Incluir Preguntas Críticas**: Active para destacar áreas problemáticas
   - **Incluir Interpretación**: Active para incluir análisis automático

4. Haga clic en **Generar PDF** para crear el documento

![Opciones de PDF](ruta/a/imagen_opciones_pdf.png)

#### Características del Reporte PDF

El reporte PDF académico incluye:

- **Resumen Ejecutivo**: Métricas clave y hallazgos principales
- **Evaluación por Profesor**: Análisis individual de cada profesor
- **Distribución de Calificaciones**: Tabla profesional de distribución
- **Preguntas Críticas**: Análisis de áreas que requieren atención
- **Comentarios Cualitativos**: Respuestas de texto categorizadas
- **Recomendaciones**: Sugerencias basadas en los resultados

### Análisis de Datos

#### Panel de Tendencias

La pestaña **Tendencias** permite analizar la evolución de las evaluaciones:

1. Seleccione el **Curso** o **Profesor**
2. Seleccione el **Período** (semestral, anual, o personalizado)
3. Seleccione las **Métricas** a mostrar
4. El sistema generará gráficos de línea mostrando la evolución

#### Análisis Comparativo

Para comparar cursos o profesores:

1. Vaya a la pestaña **Comparativo**
2. Seleccione los elementos a comparar (hasta 5)
3. Seleccione las métricas para la comparación
4. Haga clic en **Generar Comparativo**

![Análisis Comparativo](ruta/a/imagen_analisis_comparativo.png)

#### Exportación de Datos

Para análisis externos, puede exportar los datos en varios formatos:

1. Seleccione los datos que desea exportar
2. Elija el formato de exportación:
   - **Excel**: Para análisis en hojas de cálculo
   - **CSV**: Para importación en otras herramientas
   - **JSON**: Para integración con sistemas externos
3. Haga clic en **Exportar Datos**

---

## ⚙️ Configuración del Sistema

La sección de Configuración permite personalizar varios aspectos del sistema.

### Configuración General

#### Personalización de la Interfaz

1. En la pestaña **Interfaz**:
   - **Nombre del Sistema**: Personalice el título mostrado
   - **Logo**: Suba el logotipo de su institución
   - **Colores**: Personalice los colores primarios y secundarios
   - **Idioma Predeterminado**: Seleccione el idioma por defecto

#### Configuración de Correo Electrónico

Para habilitar notificaciones por correo:

1. En la pestaña **Correo Electrónico**:
   - **Servidor SMTP**: Configuración del servidor de correo
   - **Puerto**: Puerto del servidor (generalmente 587 o 465)
   - **Usuario**: Cuenta de correo para envío
   - **Contraseña**: Contraseña de la cuenta
   - **Encriptación**: Tipo de encriptación (TLS/SSL)

2. **Plantillas de Correo**:
   - Personalice las plantillas para diferentes tipos de notificación
   - Utilice los marcadores disponibles para información dinámica

### Gestión de Usuarios Administradores

#### Creación de Nuevos Usuarios

1. En la pestaña **Usuarios**:
   - Haga clic en **Nuevo Usuario**
   - Complete la información requerida (nombre, email, rol)
   - Establezca una contraseña temporal
   - Haga clic en **Crear Usuario**

#### Asignación de Roles y Permisos

El sistema incluye varios roles predefinidos:

- **Superadministrador**: Acceso completo a todas las funciones
- **Administrador**: Acceso a la mayoría de las funciones excepto configuración
- **Coordinador**: Acceso limitado a reportes y formularios
- **Visualizador**: Solo acceso de lectura a reportes

Para asignar permisos:
1. Seleccione el usuario en la lista
2. Haga clic en **Editar Permisos**
3. Marque los permisos específicos o seleccione un rol predefinido
4. Haga clic en **Guardar Permisos**

---

## ❓ Solución de Problemas Comunes

### Problemas de Acceso

| Problema | Posible Causa | Solución |
|----------|---------------|----------|
| No puedo iniciar sesión | Credenciales incorrectas | Verifique usuario y contraseña |
| | Cuenta bloqueada | Contacte al superadministrador |
| Olvidé mi contraseña | | Utilice la opción "Olvidé mi contraseña" |

### Problemas con Reportes

| Problema | Posible Causa | Solución |
|----------|---------------|----------|
| No se generan gráficos | JavaScript deshabilitado | Habilite JavaScript en su navegador |
| | Datos insuficientes | Verifique que existan evaluaciones |
| PDF no se descarga | Error en la generación | Verifique los filtros aplicados |
| | Conflicto de extensiones | Deshabilite bloqueadores de pop-ups |

### Problemas con Formularios

| Problema | Posible Causa | Solución |
|----------|---------------|----------|
| No aparecen preguntas | Preguntas inactivas | Verifique el estado de las preguntas |
| | Categoría incorrecta | Verifique la asignación de categorías |
| Formulario no disponible | Fechas de disponibilidad | Verifique las fechas configuradas |
| | Formulario inactivo | Active el formulario en su configuración |

### Contacto de Soporte

Si encuentra problemas que no puede resolver:

- **Email de Soporte**: soporte@encuestas-academicas.com
- **Documentación**: Consulte la [documentación completa](url-documentacion)
- **Horario de Soporte**: Lunes a Viernes, 9:00 - 18:00

---

## 📝 Registro de Cambios del Manual

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 03/07/2025 | Versión inicial del manual |

---

<div align="center">

*Manual del Panel Administrativo - Sistema de Encuestas Académicas*

</div>
