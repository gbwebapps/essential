const fs = require('node:fs');
const http = require('node:http');
const https = require('node:https');

// TLS locale per verificare anche i cookie Secure, senza modificare l'applicazione.
const server = https.createServer({
    cert: fs.readFileSync(process.env.INTERFACE_TLS_CERT),
    key: fs.readFileSync(process.env.INTERFACE_TLS_KEY),
}, (request, response) => {
    const upstream = http.request({
        hostname: '127.0.0.1',
        port: 8769,
        path: request.url,
        method: request.method,
        headers: { ...request.headers, 'x-forwarded-proto': 'https' },
    }, result => {
        response.writeHead(result.statusCode, result.headers);
        result.pipe(response);
    });
    upstream.on('error', () => { response.writeHead(502); response.end('Test server unavailable'); });
    request.pipe(upstream);
});
server.listen(8770, '127.0.0.1', () => process.stdout.write('Interface TLS test server: https://127.0.0.1:8770\n'));
process.on('SIGINT', () => server.close(() => process.exit()));
