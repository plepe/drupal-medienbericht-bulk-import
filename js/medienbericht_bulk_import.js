(function($, Drupal, drupalSettings) {
let list
setup()

function setup () {
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

  list = document.createElement('ul')
  div.appendChild(list)
}

function importUrl (url) {
  if (!url) { return }

  const li = document.createElement('li')
  list.appendChild(li)

  const closeBtn = document.createElement('a')
  closeBtn.href = '#'
  closeBtn.className = 'close'
  closeBtn.innerHTML = '🗙'
  closeBtn.onclick = () => {
    list.removeChild(li)
    return false
  }
  li.appendChild(closeBtn)

  const divUrl = document.createElement('div')
  divUrl.className = 'url'
  li.appendChild(divUrl)

  const a = document.createElement('a')
  a.href = url
  a.target = '_blank'
  a.appendChild(document.createTextNode(url))
  divUrl.appendChild(a)

  const divStatus = document.createElement('div')
  divStatus.className = 'status'
  li.appendChild(divStatus)
  divStatus.innerHTML = 'lade ...'

  const post_url = Drupal.url('medienbericht_bulk_import/import')
  fetch(post_url, {
    method: 'post',
    body: new URLSearchParams({ url })
  })
    .then(req => req.json())
    .then(body => {
      console.log(body)

      if (body.found) {
        const url = Drupal.url('node/' + body.found[0])
        divStatus.innerHTML = '<a target="_blank" href="' + url + '">Medienbericht bereits eingetragen</a>'
      }
      else if (body.id) {
        const url = Drupal.url('node/' + body.id + '/edit')

        divStatus.innerHTML = '<a target="_blank" href="' + url + '">Medienbericht angelegt</a>, bitte ergänze Projekte/Tags/...'
      } else if (body.prepoluateParams) {
        const url = Drupal.url('node/add/medienbericht?' + (body.prepoluateParams ?? ''))

        divStatus.innerHTML = 'Kann Medienbericht nicht anlegen, <a target="_blank" href="' + url + '">nutze diesen Link</a>'
      } else {
        const prepoluateParams = 'edit[field_url][widget][0][uri]=' + encodeURIComponent(url)
        let _url = Drupal.url('node/add/medienbericht?' + prepoluateParams)
        divStatus.innerHTML = 'Kann Medienbericht nicht anlegen, <a target="_blank" href="' + _url + '">nutze diesen Link</a>'
      }
    })
}
})(jQuery, Drupal, drupalSettings);
