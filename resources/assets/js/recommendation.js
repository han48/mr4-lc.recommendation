function LoadRecommendation(ctrl, page = undefined, perPage = undefined, pagePrefix = 'page') {
    const itemId = ctrl.dataset.contentId
    const itemName = ctrl.dataset.contentName
    const api = ctrl.dataset.contentUrl
    const buildView = ctrl.dataset.contentBuilder
    let params = {}
    params['item_name'] = itemName
    params['item_id'] = itemId
    params[pagePrefix] = page
    params[pagePrefix + 'perPage'] = perPage
    let out = []
    for (var key in params) {
        if (params.hasOwnProperty(key) && params[key]) {
            out.push(key + '=' + encodeURIComponent(params[key]))
        }
    }
    const loadings = ctrl.getElementsByClassName('mr4-lc-recommendation-loading')
    let loading = null
    if (loadings.length > 0) {
        loading = loadings[0]
    }
    loading.style.display = 'inline'
    const containers = ctrl.getElementsByClassName('mr4-lc-recommendation-items')
    let container = null
    if (containers.length > 0) {
        container = containers[0]
    }
    container.innerHTML = ''
    var xmlHttp = new XMLHttpRequest()
    xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
            const response = JSON.parse(xmlHttp.responseText)
            loading.style.display = 'none'
            if (response.data.length === 0) {
                const img = document.createElement('img')
                img.src = location.origin + '/vendor/mr4-lc/recommendation/img/empty.svg'
                img.height = 96
                container.appendChild(img)
            } else {
                if (buildView && typeof window[buildView] === 'function') {
                    window[buildView](response, container, ctrl, perPage, pagePrefix)
                } else {
                    commonBuildView(response, container, ctrl, perPage, pagePrefix)
                }
            }
        }
    }
    let url = api + "?" + out.join('&')
    xmlHttp.open("GET", url, true)
    xmlHttp.send(null)
}

function commonBuildView (response, container, ctrl, perPage, pagePrefix) {
    const items = document.createElement('div')
    items.className = 'items'
    response.data.forEach(element => {
        const div = document.createElement('div')
        div.className = 'item'
        if (element.name) {
            const name = document.createElement('div')
            name.className = 'name'
            name.innerHTML = element.name
            div.appendChild(name)
        }
        if (element.image) {
            const img = document.createElement('img')
            img.src = location.origin + '/public/' + element.image
            img.className = 'thumbnail'
            div.appendChild(img)
        }
        items.appendChild(div)
    });
    container.appendChild(items)
    const pagination = document.createElement('div')
    pagination.className = 'pagination'
    response.links.forEach(element => {
        const button = document.createElement('button')
        button.innerHTML = element.label
        button.className = element.active ? '' : 'inactive'
        pagination.appendChild(button)
        const urlParams = new URLSearchParams(element.url)
        const selectPage = urlParams.get(pagePrefix)
        button.onclick = () => {
            LoadRecommendation(ctrl, selectPage, perPage, pagePrefix)
        }
    })
    container.appendChild(pagination)
}
