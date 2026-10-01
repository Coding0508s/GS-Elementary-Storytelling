document.addEventListener('DOMContentLoaded', function () {
    const allConsent = document.getElementById('all_consent');
    const privacyConsent = document.getElementById('privacy_consent');
    const marketingConsent = document.getElementById('marketing_consent');
    const submitButton = document.getElementById('submit-btn');
    const choices = [privacyConsent, marketingConsent];

    function updateState() {
        const checkedCount = choices.filter(choice => choice.checked).length;
        allConsent.checked = checkedCount === choices.length;
        allConsent.indeterminate = checkedCount > 0 && checkedCount < choices.length;
        submitButton.disabled = !privacyConsent.checked;
    }

    allConsent.addEventListener('change', function () {
        choices.forEach(choice => { choice.checked = allConsent.checked; });
        updateState();
    });
    choices.forEach(choice => choice.addEventListener('change', updateState));
    window.addEventListener('pageshow', updateState);
    updateState();
});
