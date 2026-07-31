import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: Number(__ENV.VUS || 10),
    duration: __ENV.DURATION || '30s',
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<750'],
    },
};

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export default function () {
    const health = http.get(`${baseUrl}/health`);
    check(health, {
        'health returns 200': (response) => response.status === 200,
        'health reports ok': (response) => response.json('status') === 'ok',
    });
    sleep(1);
}
