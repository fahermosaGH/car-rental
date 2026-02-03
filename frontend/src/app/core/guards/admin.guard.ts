import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { catchError, map, of, switchMap } from 'rxjs';

export const adminGuard: CanActivateFn = (_route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isLoggedIn()) {
    auth.setReturnUrl(state.url);
    router.navigate(['/auth/login'], { queryParams: { returnUrl: state.url } });
    return false;
  }

  if (auth.currentUser) {
    if (auth.isAdmin()) return true;
    router.navigateByUrl('/cotizar');
    return false;
  }

  return auth.me().pipe(
    switchMap((me) => {
      // ✅ como ahora pediste helpers, agregamos un setter público mínimo
      auth.setCurrentUser({ email: me.email, roles: me.roles });
      return of(me.roles.includes('ROLE_ADMIN'));
    }),
    map((isAdmin) => {
      if (isAdmin) return true;
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

