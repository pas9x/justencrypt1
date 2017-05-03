function confirmDelete() {
    return confirm('Подтверждаете удаление этой конфигурации выгрузки?');
}

$(window).on('load', function() {
    $('.exportForm').on('submit', function() {
        submitWait(this);
    });
});