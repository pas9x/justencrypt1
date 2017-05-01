function switchShared() {
    var shared;
    if (elem('shared0').checked) shared = false;
    else if (elem('shared1').checked) shared = true;
    else throw new Error('WTF 1');
    if (shared) {
        $('.sshCustom').hide();
        $('.sshShared').show();
    } else {
        $('.sshCustom').show();
        $('.sshShared').hide();
        switchAuth();
    }
}

function switchAuth() {
    var authType;
    if (elem('authType1').checked) authType = 1;
    else if (elem('authType2').checked) authType = 2;
    else throw new Error('WTF 2');
    if (authType === 1) {
        $('#authPass').show();
        $('#authKey').hide();
    } else if (authType === 2) {
        $('#authPass').hide();
        $('#authKey').show();
    } else {
        throw new Error('WTF 3');
    }
}

function confirmRevoke() {
    return confirm('Подтверждаете отзыв сертификата? Удостоверяющий центр пометит сертификат как непригодный к использованию и поместит его в хранилище CRL.');
}
function confirmReissue() {
    var yes = confirm('Подтверджаете перевыпуск сертификата? При этом текущий сертификат будет отозван.');
    if (yes) alert('Выпуск сертификата может занять 1-2 минуты. Дождитесь завершения операции, не закрывайте страницу.');
    return yes;
}
function confirmDelete() {
    return confirm('Подтверджаете удаление сертификата? Помимо удаления с вашего аккаунта также будет выполнена операция отзыва сертификата.');
}

$(window).on('load', function() {
    switchShared();
});
