import { inject } from '@angular/core';
import { CanActivateFn, Router, UrlTree } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = (route, state): boolean | UrlTree => {
  const auth = inject(AuthService);
  const router = inject(Router);

  // Si está logueado, puede pasar
  if (auth.isLoggedIn()) {
    return true;
  }

  // Si NO está logueado, guardamos adónde quería ir
  auth.setReturnUrl(state.url);

  // y lo mandamos al login con redirectUrl
  return router.createUrlTree(['/auth/login'], {
    queryParams: { redirectUrl: state.url },
  });
};
