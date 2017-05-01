function onChangeAuthType() {
    var authType = parseInt(this.value);
    if (authType == 1) {
        $('#authPass').removeClass('hidden');
        $('#authKey').addClass('hidden');
    } else if (authType == 2) {
        $('#authPass').addClass('hidden');
        $('#authKey').removeClass('hidden');
    } else {
        throw new Error('Invalid value of authType: ' + authType);
    }
}

function confirmDelete() {
    return confirm('Подтверждаете удаление этого ssh-аккаунта?');
}

$(window).on('load', function() {
    $("input[name='authType']").on('click', onChangeAuthType);
});