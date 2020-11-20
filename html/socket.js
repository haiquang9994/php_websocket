(() => {
    let ___ = {
        websockets: {},
        ws_run(id, url) {
            let socket = new WebSocket(url),
                websocket = null
            try {
                websocket = this.ws_find(id)
                websocket.socket = socket
            } catch (e) {
                websocket = {
                    events: [],
                    stacks: [],
                    socket,
                    meta: {
                        count: 0,
                        callbacks: {},
                    },
                }
            }
            this.websockets[id] = websocket
            socket.onopen = (e) => {
                let { stacks } = this.ws_find(id),
                    message = stacks.shift()
                while (message) {
                    socket.send(message)
                    message = stacks.shift()
                }
            }
            socket.onmessage = (e) => {
                let content = JSON.parse(e.data),
                    { meta } = ___.ws_find(id)
                if (content.length === 3) {
                    let [_, name, data] = content,
                        events = this.websockets[id].events,
                        callbacks = events[name] || []
                    callbacks.forEach(callback => {
                        typeof callback === 'function' && callback.call(socket, data)
                    })
                } else {
                    let [index, data] = content,
                        { callbacks } = meta,
                        callback = callbacks[index]
                    delete callbacks[index]
                    if (typeof callback === 'function') {
                        callback.call(null, data)
                    }
                }
            }
            socket.onclose = () => {
                setTimeout(() => {
                    this.ws_run(id, url)
                }, 1000)
            }
        },
        ws_push_event(id, name, callback) {
            let { events } = this.ws_find(id)
            if (!events[name]) {
                events[name] = []
            }
            events[name].push(callback)
        },
        ws_push_stack(id, message) {
            let { stacks } = this.ws_find(id)
            stacks.push(message)
        },
        ws_find(id) {
            let websocket = this.websockets[id]
            if (!websocket) {
                throw 'Socket not found!'
            }
            return websocket
        },
        ws_url(url, id) {
            let queries = {},
                vars = url.split('#')[0].split('?'),
                host = vars[0].replace('http', 'ws'),
                query_string = vars[1] || '';
            query_string.split('&').map(t => t.split('=')).filter(t => t.length === 2).forEach(t => {
                queries[t[0]] = t[1]
            })
            queries.sid = id
            let query_data = []
            for (let key in queries) {
                let value = queries[key]
                query_data.push(`${key}=${value}`)
            }
            return host + '?' + query_data.join('&')
        },
        ws_emit(id, name, data, callback) {
            let { socket, meta } = ___.ws_find(id),
                message = JSON.stringify([meta.count, name, data])
            meta.callbacks[meta.count++] = callback
            if (!socket || socket.readyState !== 1) {
                ___.ws_push_stack(id, message)
            } else {
                socket.send(message)
            }
        }
    }
    class WSClient {
        constructor(url) {
            this.id = Math.random().toString(36).substr(2, 9) + Math.random().toString(36).substr(2, 9) + Math.random().toString(36).substr(2, 9)
            ___.ws_run(this.id, ___.ws_url(url, this.id))
        }
        on(name, callback) {
            ___.ws_push_event(this.id, name, callback)
        }
        emit(name, data, callback) {
            ___.ws_emit(this.id, name, data, callback)
        }
    }
    window.WSClient = WSClient
})()
