import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const allowedBase = /^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(baseUrl);
if (!allowedBase) {
  throw new Error('Safety stop: BASE_URL must be localhost or 127.0.0.1 over HTTP.');
}

const optionalPaths = [__ENV.PRODUCT_PATH || '', __ENV.CATEGORY_PATH || '', __ENV.SEARCH_PATH || '']
  .filter(Boolean);
for (const path of optionalPaths) {
  const allowed = /^\/(product|category)\/[A-Za-z0-9-]+$/.test(path)
    || /^\/shop(?:\?[^#]*)?$/.test(path);
  if (!allowed) {
    throw new Error('Optional paths must be local read-only product/category/shop routes.');
  }
}

const stages = [
  { duration: '20s', target: 1 },
  { duration: '20s', target: 1 },
  { duration: '1m', target: 5 },
  { duration: '2m', target: 5 },
  { duration: '1m', target: 10 },
  { duration: '2m', target: 10 },
  { duration: '1m', target: 20 },
  { duration: '2m', target: 20 },
  { duration: '1m', target: 50 },
  { duration: '2m', target: 50 },
];

if (__ENV.ALLOW_100_VUS === 'true') {
  stages.push({ duration: '1m', target: 100 });
  stages.push({ duration: '2m', target: 100 });
}

export const options = {
  stages,
  summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

const paths = ['/', '/shop', ...optionalPaths];

export default function () {
  const path = paths[(__VU + __ITER) % paths.length];
  const response = http.get(baseUrl + path, { tags: { path } });
  check(response, {
    'read-only route returns success': (res) => res.status >= 200 && res.status < 400,
  });
  sleep(1);
}
