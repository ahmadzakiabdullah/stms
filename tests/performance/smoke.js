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
const authEmail = __ENV.AUTH_EMAIL;
const authPassword = __ENV.AUTH_PASSWORD;

export function setup() {
    if (!authEmail || !authPassword) return { authenticated: false };

    const loginPage = http.get(`${baseUrl}/login`);
    const csrfToken = loginPage.html().find('meta[name="csrf-token"]').attr('content');
    const response = http.post(`${baseUrl}/login`, {
        email: authEmail,
        password: authPassword,
        _token: csrfToken,
    }, { redirects: 0 });

    check(response, {
        'authenticated login redirects': (result) => [302, 303].includes(result.status),
    });

    const sessionCookie = response.cookies.laravel_session?.[0]?.value;
    check(sessionCookie, {
        'authenticated session cookie issued': (value) => Boolean(value),
    });

    return { authenticated: Boolean(sessionCookie), sessionCookie };
}

export default function (data) {
    const health = http.get(`${baseUrl}/health`);
    check(health, {
        'health returns 200': (response) => response.status === 200,
        'health reports ok': (response) => response.json('status') === 'ok',
    });

    if (data.authenticated) {
        const dashboard = http.get(`${baseUrl}/dashboard`, {
            headers: { Cookie: `laravel_session=${data.sessionCookie}` },
        });
        check(dashboard, {
            'authenticated dashboard returns 200': (response) => response.status === 200,
            'authenticated dashboard is not login page': (response) => !response.url.endsWith('/login'),
        });
    }
    sleep(1);
}
