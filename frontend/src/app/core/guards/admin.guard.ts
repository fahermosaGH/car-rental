import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { catchError, map, of, switchMap } from 'rxjs';

export const adminGuard: CanActivateFn = (_route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  // 1) No token => login con returnUrl
  if (!auth.isLoggedIn()) {
    auth.setReturnUrl(state.url);
    router.navigate(['/auth/login'], { queryParams: { returnUrl: state.url } });
    return false;
  }

  // 2) Ya tengo user en memoria
  if (auth.currentUser) {
    if (auth.isAdmin()) return true;
    router.navigateByUrl('/cotizar');
    return false;
  }

  // 3) Refresh: cargar /me y decidir
  return auth.loadMe().pipe(
    map((me) => {
      if (me.roles.includes('ROLE_ADMIN')) return true;
      router.navigateByUrl('/cotizar');
      return false;
    }),
    catchError(() => {
      auth.logout();
      auth.setReturnUrl(state.url);
      router.navigate(['/auth/login'], { queryParams: { returnUrl: state.url } });
      return of(false);
    })
  );
};
