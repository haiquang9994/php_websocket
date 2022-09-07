(() => {
    function WSClient(url, binary = false) {
        let __id =
            Math.random().toString(36).substring(2, 11) +
            Math.random().toString(36).substring(2, 11) +
            Math.random().toString(36).substring(2, 11);
        let socket = undefined;
        let events = [];
        let stacks = [];
        let count = 0;
        let callbacks = {};
        let ping = undefined;

        function __message(message) {
            if (binary) {
                const len = message.length;
                const bytes = new Uint8Array(len);
                for (let i = 0; i < len; i++) {
                    bytes[i] = message.charCodeAt(i);
                }
                return bytes.buffer;
            }
            return message;
        }

        function run(url) {
            socket = new WebSocket(url);
            socket.binaryType = "arraybuffer";
            socket.onopen = (e) => {
                let message = stacks.shift();
                while (message) {
                    socket.send(__message(message));
                    message = stacks.shift();
                }
                ping = setInterval(() => {
                    socket.send(__message("" + count++));
                }, 15000);
            };

            socket.onmessage = (e) => {
                try {
                    let edata = e.data;
                    if (edata instanceof ArrayBuffer) {
                        edata = new TextDecoder().decode(edata);
                    }
                    let content = JSON.parse(edata);
                    if (!(content instanceof Array)) {
                        return;
                    }
                    if (content.length === 3) {
                        let [, name, data] = content;
                        let _callbacks = events[name] || [];
                        _callbacks.forEach((callback) => {
                            typeof callback === "function" && callback.call(socket, data);
                        });
                    } else {
                        let [index, data] = content;
                        let callback = callbacks[index];
                        delete callbacks[index];
                        typeof callback === "function" && callback.call(null, data);
                    }
                } catch (e) {}
            };

            socket.onclose = () => {
                clearInterval(ping);
                setTimeout(() => run(url), 2000);
            };
        }

        function pushEvent(name, callback) {
            if (!events[name]) {
                events[name] = [];
            }
            events[name].push(callback);
        }

        function emit(name, data, callback) {
            const message = JSON.stringify([count, name, data]);
            callbacks[count++] = callback;
            if (!socket || socket.readyState !== 1) {
                stacks.push(message);
            } else {
                socket.send(__message(message));
            }
        }

        function createUrl(url) {
            let queries = {},
                vars = url.split("#")[0].split("?"),
                host = vars[0].replace("http", "ws"),
                query_string = vars[1] || "";
            query_string
                .split("&")
                .map((t) => t.split("="))
                .filter((t) => t.length === 2)
                .forEach((t) => {
                    queries[t[0]] = t[1];
                });
            queries.sid = __id;
            let query_data = [];
            for (let key in queries) {
                let value = queries[key];
                query_data.push(`${key}=${value}`);
            }
            return host + "?" + query_data.join("&");
        }

        run(createUrl(url));

        this.id = __id;

        this.on = (name, callback) => {
            pushEvent(name, callback);
        };
        this.emit = (name, data, callback) => {
            emit(name, data, callback);
        };
    }
    window.WSClient = WSClient;
})();
