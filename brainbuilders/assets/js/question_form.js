document.addEventListener('DOMContentLoaded', function() {
    const questionType = document.getElementById('questionType');
    const questionFields = document.getElementById('questionFields');
    
    function loadQuestionFields(type) {
        fetch(`partials/question_fields_${type}.php`)
            .then(response => response.text())
            .then(html => {
                questionFields.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading question fields:', error);
                questionFields.innerHTML = '<div class="alert alert-danger">Could not load question type fields</div>';
            });
    }
    
    questionType.addEventListener('change', function() {
        loadQuestionFields(this.value);
    });
    
    if (questionType.value) {
        loadQuestionFields(questionType.value);
    }
});