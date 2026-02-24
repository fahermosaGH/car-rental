import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

export type AuditAction = '' | 'create' | 'update' | 'delete' | 'custom';

export interface AuditRow {
  id: number;
  occurredAt: string;
  actorEmail: string | null;
  action: string;
  entityClass: string;
  entityLabel: string;
  entityId: string | null;
  changes: any | null;
  meta: any | null;
}

export interface AuditListParams {
  actor?: string;
  action?: string;
  entity?: string;
  entityId?: string;
}

@Injectable({ providedIn: 'root' })
export class AdminAuditoriaService {
  private base = '/api/admin/audit';

  constructor(private http: HttpClient) {}

  list(params?: AuditListParams): Observable<AuditRow[]> {
    let hp = new HttpParams();
    if (params?.actor) hp = hp.set('actor', params.actor);
    if (params?.action) hp = hp.set('action', params.action);
    if (params?.entity) hp = hp.set('entity', params.entity);
    if (params?.entityId) hp = hp.set('entityId', params.entityId);

    return this.http.get<AuditRow[]>(this.base, { params: hp });
  }
}