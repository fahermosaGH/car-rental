import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RequisitosAlquiler } from './requisitos-alquiler';

describe('RequisitosAlquiler', () => {
  let component: RequisitosAlquiler;
  let fixture: ComponentFixture<RequisitosAlquiler>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RequisitosAlquiler]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RequisitosAlquiler);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
