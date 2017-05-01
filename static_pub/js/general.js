function showHint() {
  var text = $(this).attr('data-hint');
  $('#hint').text(text);
  $('#hint').show();
}

function closeHint() {
  $('#hint').hide();
}

function elem(id) {
  return document.getElementById(id);
}

function submitWait(form, label) {
  if (!label) label = 'Подождите...';
  var btn = $("input[type='submit']", form);
  btn.attr('disabled', 'yes');
  btn.val(label);
}

$(window).on('load', function() {
  $('select[data-selected]').each(function(index, elem) {
    var selected = elem.getAttribute('data-selected');
    if (selected) elem.value = selected;
  });
  $('input[data-hint], textarea[data-hint], select[data-hint]').on('blur', closeHint);
  $('input[data-hint], textarea[data-hint], select[data-hint]').on('focus', showHint);

  $('a[data-target]').each(function(index, elem) {
    var target = elem.getAttribute('data-target');
    if (target !== '_blank') return;
    elem.setAttribute('target', '_blank');
    var relCurrent = elem.getAttribute('rel');
    if (relCurrent === '' || relCurrent === null) {
      relNew = 'noopener';
    } else {
      relNew = relCurrent;
      var rels = relCurrent.split(/\s+/);
      if (rels.indexOf('noopener') < 0) relNew += ' noopener';
    }
    if (relNew !== relCurrent) {
      elem.setAttribute('rel', relNew);
    }
  });
});
