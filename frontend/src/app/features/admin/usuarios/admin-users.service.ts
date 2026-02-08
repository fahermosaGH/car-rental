import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { Observable } from 'rxjs';

export interface AdminUserRow {
  id: number;
  email: string;
  roles: string[];
  firstName: string;
  lastName: string;
  createdAt: string;
  isActive: boolean;
  profileComplete: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminUsersService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  list(showInactive: boolean): Observable<AdminUserRow[]> {
    const qs = showInactive ? '?showInactive=1' : '';
    return this.http.get<AdminUserRow[]>(`${this.apiUrl}/admin/users${qs}`);
  }

  updateRoles(id: number, roles: string[]): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin/users/${id}/roles`, { roles });
  }

  setActive(id: number, isActive: boolean): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin/users/${id}/active`, { isActive });
  }
}
