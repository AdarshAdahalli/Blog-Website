// User Panel Interactions
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic Query Form helper
    const askQuestionBtn = document.getElementById('btn-ask-question');
    if (askQuestionBtn) {
        askQuestionBtn.addEventListener('click', function() {
            const querySection = document.getElementById('query-form-section');
            if (querySection) {
                querySection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});
