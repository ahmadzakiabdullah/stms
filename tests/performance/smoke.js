import http from 'k6/http';
import { check, fail, sleep } from 'k6';

export const options = {
    vus: Number(__ENV.VUS || 10),
    duration: __ENV.DURATION || '30s',
    noCookiesReset: true,
    thresholds: {
        checks: ['rate==1'],
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<750'],
    },
};

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const healthToken = (__ENV.HEALTH_TOKEN || '').trim();
const authLogins = (__ENV.AUTH_LOGINS || __ENV.AUTH_LOGIN || __ENV.AUTH_EMAIL || '')
    .split(',').map((value) => value.trim()).filter(Boolean);
const authPasswords = (__ENV.AUTH_PASSWORDS || __ENV.AUTH_PASSWORD || '')
    .split(',').map((value) => value.trim()).filter(Boolean);
let authenticated = false;

function credentialForVu() {
    if (!authLogins.length || !authPasswords.length) return null;

    const index = (__VU - 1) % authLogins.length;
    return {
        login: authLogins[index],
        password: authPasswords[index] || authPasswords[0],
    };
}

function authenticate() {
    const credential = credentialForVu();
    if (!credential) return false;

    const jar = http.cookieJar();
    const loginPage = http.get(`${baseUrl}/login`);
    const xsrfCookie = jar.cookiesForURL(baseUrl)['XSRF-TOKEN']?.[0];

    if (!check(loginPage, { 'login page returns 200': (response) => response.status === 200 }) || !xsrfCookie) {
        fail('Login page did not issue an XSRF-TOKEN cookie.');
    }

    const response = http.post(`${baseUrl}/login`, {
        login: credential.login,
        password: credential.password,
    }, {
        redirects: 0,
        headers: {
            Accept: 'text/html,application/xhtml+xml',
            'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie),
        },
    });

    return check(response, {
        'authenticated login redirects': (result) => [302, 303].includes(result.status),
        'session cookie issued': () => Object.keys(jar.cookiesForURL(baseUrl))
            .some((name) => name !== 'XSRF-TOKEN'),
    });
}

export default function () {
    const health = http.get(`${baseUrl}/health`, {
        headers: healthToken ? { 'X-Health-Token': healthToken } : {},
    });
    check(health, {
        'health returns 200': (response) => response.status === 200,
        'health reports ok': (response) => response.json('status') === 'ok',
    });

    if (!authenticated && credentialForVu()) authenticated = authenticate();

    if (authenticated) {
        const dashboard = http.get(`${baseUrl}/dashboard`, { redirects: 0 });
        check(dashboard, {
            'authenticated dashboard returns 200': (response) => response.status === 200,
            'authenticated dashboard is not redirected to login': (response) => ![301, 302, 303].includes(response.status),
        });
    }

    sleep(1);
}

export function handleSummary(data) {
    const path = __ENV.K6_SUMMARY_PATH || 'test-results/k6-summary.json';

    return {
        [path]: JSON.stringify(data, null, 2),
        stdout: `k6 summary written to ${path}\n`,
    };
}
