import { HttpInterceptorFn } from '@angular/common/http';

export const authTokenInterceptor: HttpInterceptorFn = (req, next) => {
  const token = localStorage.getItem('auth_token');

  // No tocar auth endpoints
  const isAuthEndpoint =
    req.url.includes('/login_check') ||
    req.url.includes('/register') ||
    req.url.includes('/forgot-password');

  if (isAuthEndpoint) return next(req);

  if (!token) return next(req);

  if (req.headers.has('Authorization')) return next(req);

  return next(
    req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    })
  );
};
