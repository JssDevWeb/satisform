/**
 * ============================================
 * SISTEMA DE ENCUESTAS ACADÉMICAS - JAVASCRIPT ADAPTADO
 * ============================================
 * Archivo: survey_fixed.js
 * Descripción: Script adaptado para funcionar con la BBDD relacional (centro_formacion).
 * Flujo implementado: Selección Curso -> Selección Módulo -> Evaluación Curso -> Profesor 1 -> ... -> Envío
 * Modificación: Se han adaptado los métodos de carga y navegación. Se ha preservado el resto del código original.
 * ============================================
 */

// Debug mode - cambiar a false en producción
const DEBUG_MODE = false;

// Función de debug
function debugLog(message, data = null) {
    if (DEBUG_MODE) {
        console.log(`[SURVEY ADAPTADO] ${message}`, data || '');
    }
}

// Configuración global
const CONFIG = {
    API_BASE_URL: './api/',
    ENDPOINTS: {
        // Endpoints adaptados a la nueva BBDD
        cursos: 'get_cursos.php',
        modulos: 'get_modulos_activos.php',
        profesores_y_formulario: 'get_profesores.php',
        preguntas: 'get_preguntas.php',
        procesar: 'submit_token_survey.php'
    }
};

// Estado global de la aplicación
const AppState = {
    currentStep: 1, // 1: selección, 2: curso, 3: profesores
    // Nuevos estados para el flujo Curso -> Modulo
    selectedCursoId: null,
    selectedModuloId: null,
    formularioId: null,
    profesores: [],
    currentProfessorIndex: 0,
    courseQuestions: [],
    professorQuestions: [],
    startTime: null,
    responses: {
        formulario_id: null,
        course_answers: {},
        professor_answers: {}
    }
};

// Utilidades 
const Utils = {
    async fetchAPI(endpoint, options = {}) {
        const url = CONFIG.API_BASE_URL + endpoint;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        debugLog(`Haciendo petición a: ${url}`, options);
        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            
            if (!response.ok) {
                let errorMessage = `HTTP Error: ${response.status} ${response.statusText}`;
                try {
                    const errorData = await response.json();
                    if (errorData.error) errorMessage += ` - ${errorData.error}`;
                    if (errorData.debug_message) errorMessage += ` (Debug: ${errorData.debug_message})`;
                } catch (e) {
                    // Sin cuerpo JSON
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            debugLog('Datos recibidos:', data);
            
            if (data.success === false) { // Comprobar explícitamente el false
                throw new Error(data.message || 'La API devolvió un error sin mensaje.');
            }

            return data;
        } catch (error) {
            debugLog('Error en petición:', error);
            throw error;
        }
    },

    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert_' + Date.now();
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        alertContainer.insertAdjacentHTML('beforeend', alertHTML);
        if (duration > 0) {
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement) new bootstrap.Alert(alertElement).close();
            }, duration);
        }
    },

    // ✅ VERSIÓN CORREGIDA DE LA FUNCIÓN
    showStep(stepIdentifier) { // Acepta número o string (ID)
        const currentVisibleStep = document.querySelector('.survey-step[style*="block"]');
    
        const showNewStep = () => {
            const stepElement = typeof stepIdentifier === 'number'
                ? document.getElementById(`step${stepIdentifier}-${this.getStepName(stepIdentifier)}`)
                : document.getElementById(stepIdentifier); // Busca por ID si es un string

            if (stepElement) {
                stepElement.style.display = 'block';
                stepElement.style.opacity = '0';
                stepElement.style.transform = 'translateY(20px)';
                stepElement.offsetHeight;
                stepElement.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                stepElement.style.opacity = '1';
                stepElement.style.transform = 'translateY(0)';
                setTimeout(() => {
                    stepElement.style.transition = '';
                    stepElement.style.transform = '';
                }, 400);
            }
        };
    
        if (currentVisibleStep) {
            currentVisibleStep.style.transition = 'opacity 0.3s ease-in, transform 0.3s ease-in';
            currentVisibleStep.style.opacity = '0';
            currentVisibleStep.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.querySelectorAll('.survey-step').forEach(step => {
                    step.style.display = 'none';
                    step.style.transition = '';
                    step.style.opacity = '';
                    step.style.transform = '';
                });
                showNewStep();
            }, 300);
        } else {
            document.querySelectorAll('.survey-step').forEach(step => step.style.display = 'none');
            showNewStep();
        }
    
        const isSelectionOrSummary = (typeof stepIdentifier === 'string' && stepIdentifier.includes('summary')) || stepIdentifier === 1;
        const progressBar = document.getElementById('progressBar');
        const resetSection = document.getElementById('resetSection');
    
        if (isSelectionOrSummary) {
            progressBar.style.display = 'none';
            resetSection.style.display = 'none';
        } else {
            progressBar.style.display = 'block';
            resetSection.style.display = 'block';
        }
    
        if(typeof stepIdentifier === 'number') {
            AppState.currentStep = stepIdentifier;
            this.updateProgress();
        }
    },

    getStepName(stepNumber) {
        const stepNames = { 1: 'course-selection', 2: 'course-evaluation', 3: 'professor-evaluation' };
        return stepNames[stepNumber] || '';
    },

    updateProgress() {
        const { currentStep, profesores, currentProfessorIndex } = AppState;
        let totalSteps = 1 + (profesores.length > 0 ? profesores.length : 1); // 1 Módulo + N Profesores
        let currentStepNumber = 0;
        if(currentStep === 2) currentStepNumber = 1;
        if(currentStep === 3) currentStepNumber = 1 + currentProfessorIndex + 1;
        
        const progressPercent = (currentStepNumber / totalSteps) * 100;
        document.getElementById('progressText').textContent = `Paso ${currentStepNumber} de ${totalSteps}`;
        document.getElementById('progressBarFill').style.width = `${progressPercent}%`;
        
        let stepText = '';
        switch(currentStep) {
            case 1: stepText = 'Selección'; break;
            case 2: stepText = 'Evaluando el Módulo'; break;
            case 3: stepText = `Evaluando a: ${profesores[currentProfessorIndex]?.nombre || ''}`; break;
        }
        document.getElementById('currentStepText').textContent = stepText;
    },

    scrollToTop(delay = 0) {
        const forceScroll = () => window.scrollTo({ top: 0, behavior: 'smooth' });
        if (delay > 0) setTimeout(forceScroll, delay);
        else forceScroll();
    }
};

// Manejo de preguntas (CÓDIGO ORIGINAL PRESERVADO)
const QuestionManager = {
    async loadCourseQuestions() {
        try {
            const data = await Utils.fetchAPI(`${CONFIG.ENDPOINTS.preguntas}?seccion=curso`);
            if (data.data && data.data.length > 0) {
                AppState.courseQuestions = data.data;
                this.renderQuestions(data.data, 'courseQuestions', 'course');
                document.getElementById('courseTitle').textContent = `${AppState.cursoNombre} / ${AppState.moduloNombre}`;
            } else {
                Utils.showAlert('No hay preguntas disponibles para este curso', 'warning');
            }
        } catch (error) {
        Utils.showAlert(`Error al cargar las preguntas del curso: ${error.message}`, 'danger', 10000);
        }
    },
    
    async loadProfessorQuestions() {
        try {
            const data = await Utils.fetchAPI(`${CONFIG.ENDPOINTS.preguntas}?seccion=profesor`);
            if (data.data && data.data.length > 0) {
                AppState.professorQuestions = data.data;
                this.renderProfessorQuestions();
            } else {
                Utils.showAlert('No hay preguntas para evaluar profesores', 'warning');
            }
        } catch (error) {
            Utils.showAlert('Error al cargar las preguntas del profesor', 'danger');
        }
    },
    
    renderQuestions(questions, containerId, prefix) {
        const container = document.getElementById(containerId);
        let tableHTML = `<table class="evaluation-table"><thead><tr><th>CONCEPTO A VALORAR</th><th class="emoji-header">Excelente</th><th class="emoji-header">Bueno</th><th class="emoji-header">Correcto</th><th class="emoji-header">Regular</th><th class="emoji-header">Deficiente</th></tr></thead><tbody>`;
        let otherQuestionsHTML = '';
        questions.forEach((question) => {
            const questionType = question.tipo || 'escala';
            if (questionType === 'escala') {
                tableHTML += this.createScaleHTML(question, `${prefix}_${question.id}`);
            } else {
                otherQuestionsHTML += `<div class="non-scale-question">${this.createNonScaleQuestionHTML(question, prefix)}</div>`;
            }
        });
        tableHTML += `</tbody></table>`;
        container.innerHTML = tableHTML + otherQuestionsHTML;
        this.addEmojiCellListeners(container);
    },
    
    renderProfessorQuestions() {
        const profesor = AppState.profesores[AppState.currentProfessorIndex];
        if (!profesor) return;
        document.getElementById('professorTitle').textContent = profesor.nombre;
        document.getElementById('professorCounter').textContent = `Profesor ${AppState.currentProfessorIndex + 1} de ${AppState.profesores.length}`;
        this.renderQuestions(AppState.professorQuestions, 'professorQuestions', `professor_${profesor.id}`);
        this.updateProfessorNavigation();
    },

    updateProfessorNavigation() {
        const prevBtn = document.getElementById('prevProfessorBtn');
        const nextBtn = document.getElementById('nextProfessorBtn');
        const submitBtn = document.getElementById('submitBtn');
        const isFirst = AppState.currentProfessorIndex === 0;
        const isLast = AppState.currentProfessorIndex === AppState.profesores.length - 1;
        prevBtn.style.display = isFirst ? 'none' : 'inline-block';
        nextBtn.style.display = isLast ? 'none' : 'inline-block';
        submitBtn.style.display = isLast ? 'inline-block' : 'none';
    },

    createScaleHTML(question, inputName) {
        const scaleOptions = [{ v: 10, t: 'Excelente' }, { v: 7, t: 'Bueno' }, { v: 5, t: 'Correcto' }, { v: 3, t: 'Regular' }, { v: 1, t: 'Deficiente' }];
        let html = `<tr data-question-id="${question.id}"><td class="question-text">${question.texto || 'Pregunta sin texto'}</td>`;
        scaleOptions.forEach(option => {
            html += `<td class="emoji-cell" data-label="${option.t}"><input type="radio" name="${inputName}" value="${option.v}" id="${inputName}_${option.v}" ${question.es_obligatoria ? 'required' : ''}></td>`;
        });
        html += '</tr>';
        return html;
    },

    createNonScaleQuestionHTML(question, prefix) {
        const inputName = `${prefix}_${question.id}`;
        let html = `<div data-question-id="${question.id}"><h6>${question.texto}</h6>`;
        if (question.tipo === 'texto') {
            html += `<textarea class="form-control" name="${inputName}" rows="3" ${question.es_obligatoria ? 'required' : ''}></textarea>`;
        }
        html += '</div>';
        return html;
    },
    
    addEmojiCellListeners(container) {
        const emojiMap = { '10': '😃', '7': '🙂', '5': '😐', '3': '😕', '1': '😞' };
        container.querySelectorAll('.emoji-cell').forEach(cell => {
            const radioInput = cell.querySelector('input[type="radio"]');
            if (radioInput) {
                const existingEmoji = cell.querySelector('.emoji-only');
                if (existingEmoji) existingEmoji.remove();
                const emojiSpan = document.createElement('span');
                emojiSpan.className = 'emoji-only';
                emojiSpan.textContent = emojiMap[radioInput.value];
                cell.appendChild(emojiSpan);
                cell.addEventListener('click', () => {
                    const row = cell.closest('tr');
                    row.querySelectorAll('.emoji-cell').forEach(c => c.classList.remove('selected'));
                    cell.classList.add('selected');
                    radioInput.checked = true;
                    radioInput.dispatchEvent(new Event('change', {bubbles: true}));
                });
            }
        });
    }
};

// Manejo de navegación (MÉTODOS ADAPTADOS)
const NavigationManager = {
    
    // AÑADE ESTA NUEVA FUNCIÓN DENTRO DE NavigationManager
    
    startSurvey() {
        debugLog('Iniciando encuesta (evaluación de módulo)');
        AppState.startTime = Date.now();
        Utils.showStep(2);
        QuestionManager.loadCourseQuestions();
        Utils.scrollToTop();
    },

    nextToProfessors() {
        if (!this.validateCurrentStep()) {
            Utils.showAlert('❌ Por favor complete todas las preguntas requeridas.', 'warning');
            return;
        }
        this.saveCourseAnswers();
        if (AppState.profesores.length > 0) {
            Utils.showStep(3);
            QuestionManager.loadProfessorQuestions();
        } else {
            debugLog('No hay profesores, procediendo a enviar.');
            SubmissionManager.submitSurvey();
        }
        Utils.scrollToTop();
    },

    nextProfessor() {
        if (!this.validateCurrentStep()) {
            Utils.showAlert('❌ Por favor complete las preguntas sobre este profesor.', 'warning');
            return;
        }
        this.saveProfessorAnswers();
        if (AppState.currentProfessorIndex < AppState.profesores.length - 1) {
            AppState.currentProfessorIndex++;
            QuestionManager.renderProfessorQuestions();
            Utils.scrollToTop();
        }
    },

    prevProfessor() {
        if (AppState.currentProfessorIndex > 0) {
            this.saveProfessorAnswers();
            AppState.currentProfessorIndex--;
            QuestionManager.renderProfessorQuestions();
            Utils.scrollToTop();
        }
    },

    validateCurrentStep() {
        const currentStepElement = document.querySelector('.survey-step[style*="block"]');
        if (!currentStepElement) return true;
        let isValid = true;
        let firstErrorElement = null;
        currentStepElement.querySelectorAll('.missing-answer, .is-invalid').forEach(el => el.classList.remove('missing-answer', 'is-invalid'));
        currentStepElement.querySelectorAll('[required]').forEach(input => {
            const container = input.closest('tr') || input.closest('div[data-question-id]');
            let questionIsValid = true;
            if (input.type === 'radio') {
                if (!currentStepElement.querySelector(`[name="${input.name}"]:checked`)) questionIsValid = false;
            } else if (!input.value.trim()) {
                questionIsValid = false;
            }
            if (!questionIsValid) {
                isValid = false;
                if (container) {
                    container.classList.add(container.tagName === 'TR' ? 'missing-answer' : 'is-invalid');
                    if (!firstErrorElement) firstErrorElement = container;
                }
            }
        });
        if (!isValid && firstErrorElement) {
            firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return isValid;
    },

    saveCourseAnswers() {
        const container = document.getElementById('courseQuestions');
        container.querySelectorAll('input:checked, textarea').forEach(input => {
            const qId = this.extractQuestionId(input.name);
            if (qId && input.value) AppState.responses.course_answers[qId] = input.value;
        });
        debugLog('Respuestas del curso guardadas:', AppState.responses.course_answers);
    },

    saveProfessorAnswers() {
        const profesor = AppState.profesores[AppState.currentProfessorIndex];
        if (!profesor) return;
        if (!AppState.responses.professor_answers[profesor.id]) AppState.responses.professor_answers[profesor.id] = {};
        const container = document.getElementById('professorQuestions');
        container.querySelectorAll('input:checked, textarea').forEach(input => {
            const qId = this.extractQuestionId(input.name);
            if (qId && input.value) AppState.responses.professor_answers[profesor.id][qId] = input.value;
        });
        debugLog(`Respuestas del profesor ${profesor.id} guardadas:`, AppState.responses.professor_answers[profesor.id]);
    },

    extractQuestionId: (name) => name.split('_').pop(),

    resetSurvey() {
        if (confirm('¿Está seguro? Se perderán todas las respuestas.')) {
            window.location.reload();
        }
    }
};

// Manejo del envío
        const SubmissionManager = {
                        
            async submitSurvey() {
                debugLog('--- Se ha pulsado el botón de envío final. Iniciando submitSurvey ---');
                try {
                    if (!NavigationManager.validateCurrentStep()) {
                        Utils.showAlert('❌ Por favor complete todas las preguntas requeridas.', 'warning');
                        return;
                    }
                    debugLog('Paso 1: Validación de campos OK.');

                    NavigationManager.saveProfessorAnswers();
                    debugLog('Paso 2: Respuestas de profesores guardadas en el estado de la app.');

                    document.getElementById('loadingOverlay').style.display = 'flex';
                    debugLog('Paso 3: Mostrando overlay de "cargando".');

                    const submissionData = this.prepareSubmissionData();
                    debugLog('Paso 4: Datos para el envío preparados con éxito.');

                    debugLog('Paso 5: Datos que se van a enviar:', submissionData);

                    const response = await Utils.fetchAPI(CONFIG.ENDPOINTS.procesar, {
                        method: 'POST',
                        body: JSON.stringify(submissionData)
                    });
                    debugLog('Paso 6: Petición a la API realizada y respuesta recibida.');
                    
                    this.handleSuccessfulSubmission(response);

                } catch (error) {
                    // ¡Este es el log más importante si algo falla!
                    debugLog('!!! ERROR CAPTURADO EN EL BLOQUE CATCH !!!', error);
                    Utils.showAlert('Error al procesar la encuesta. Revise la consola para más detalles.', 'danger');
                } finally {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    debugLog('--- Fin de la ejecución de submitSurvey ---');
                }
            },

    prepareSubmissionData() {
        // 1. Unificar todas las respuestas en un solo array, como lo espera el PHP.
        const todasLasRespuestas = [];

        // Añadir respuestas del curso
        for (const preguntaId in AppState.responses.course_answers) {
            todasLasRespuestas.push({
                pregunta_id: preguntaId,
                valor: AppState.responses.course_answers[preguntaId],
                profesor_id: null // No hay profesor para las preguntas del curso
            });
        }

        // Añadir respuestas de los profesores
        for (const profesorId in AppState.responses.professor_answers) {
            for (const preguntaId in AppState.responses.professor_answers[profesorId]) {
                todasLasRespuestas.push({
                    pregunta_id: preguntaId,
                    valor: AppState.responses.professor_answers[profesorId][preguntaId],
                    profesor_id: profesorId // Asociamos la respuesta al profesor correcto
                });
            }
        }

        // 2. Construir el objeto final para la API con el formato correcto.
        const data = {
            'token': surveyAuthData.token,
            'formulario_id': AppState.formularioId,
            'ID_Modulo': AppState.selectedModuloId,
            'tiempo_completado': this.calculateCompletionTime(),
            'respuestas': todasLasRespuestas
        };

        debugLog('Datos finales formateados para enviar:', data);
        return data;
    },

    calculateCompletionTime() {
        if (!AppState.startTime) return 120;
        return Math.floor((Date.now() - AppState.startTime) / 1000);
    },

    handleSuccessfulSubmission(response) {
        window.location.href = `gracias.html?id=${response?.data?.encuesta_id || ''}`;
    }
};

// Event Listeners (VERSIÓN SIMPLIFICADA PARA FLUJO CON TOKEN)
// Event Listeners (VERSIÓN ROBUSTA Y CORREGIDA)
function initializeEventListeners() {
    
    // Función auxiliar para añadir listeners de forma segura
    const safeAddListener = (id, event, handler) => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener(event, handler);
        } else {
            debugLog(`ADVERTENCIA: No se encontró el elemento con ID #${id} para añadir un listener.`);
        }
    };

    // --- Asignación de todos los eventos de la aplicación ---
    // Botones de navegación DENTRO de la encuesta
    safeAddListener('nextToProfessorsBtn', 'click', (e) => {
        e.preventDefault();
        NavigationManager.nextToProfessors();
    });

    safeAddListener('nextProfessorBtn', 'click', (e) => {
        e.preventDefault();
        NavigationManager.nextProfessor();
    });

    safeAddListener('prevProfessorBtn', 'click', (e) => {
        e.preventDefault();
        NavigationManager.prevProfessor();
    });

    safeAddListener('submitBtn', 'click', (e) => {
        e.preventDefault();
        SubmissionManager.submitSurvey();
    });
    
    // Botón de reinicio
    safeAddListener('resetForm', 'click', NavigationManager.resetSurvey);
};


// Inicialización (VERSIÓN FINAL)
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Inicializando sistema de encuestas (modo DIRECTO).');

    // El PHP ya ha validado el token. Si estamos aquí, tenemos datos.
    if (typeof surveyAuthData !== 'undefined' && surveyAuthData.surveyInfo) {
        const info = surveyAuthData.surveyInfo;

        // 1. Pre-cargamos los datos en el estado de la App
        AppState.selectedCursoId = info.ID_Curso;
        AppState.selectedModuloId = info.ID_Modulo;
        AppState.formularioId = info.formulario_id;
        AppState.profesores = info.profesores || [];
        AppState.responses.formulario_id = info.formulario_id;
        AppState.cursoNombre = info.curso_nombre;
        AppState.moduloNombre = info.modulo_nombre;

        // 2. Iniciamos la encuesta inmediatamente
        NavigationManager.startSurvey();

    } else {
        debugLog('ERROR CRÍTICO: No se encontraron datos de encuesta. La página no puede funcionar.');
        Utils.showAlert('No se pudo cargar la información de la encuesta. El enlace puede ser inválido.', 'danger');
    }

    // 3. Inicializamos los listeners para los botones de la encuesta (siguiente, previo, etc.)
    initializeEventListeners();
});