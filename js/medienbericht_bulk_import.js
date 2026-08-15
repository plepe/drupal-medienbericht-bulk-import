(function($, Drupal, drupalSettings) {
console.log('here')
setup()
function setup () {
  console.log('setup')
  const div = document.getElementById('import')

  const form = document.createElement('form')
  form.onsubmit = () => {
    const list = textarea.value.split('\n')
    list.forEach(url => importUrl(url))

    textarea.value = ''
    return false
  }
  div.appendChild(form)

  const textarea = document.createElement('textarea')
  textarea.id = 'urls'
  form.appendChild(textarea)

  const input = document.createElement('input')
  input.type = 'submit'
  input.value = 'Import'
  form.appendChild(input)
}

function importUrl (url) {
  if (!url) { return }

  const post_url = Drupal.url('medienbericht_bulk_import/import')
  fetch(post_url, {
    method: 'post',
    body: new URLSearchParams({ url })
  })
    .then(req => req.text())
    .then(body => window.alert(body))
}
})(jQuery, Drupal, drupalSettings);
