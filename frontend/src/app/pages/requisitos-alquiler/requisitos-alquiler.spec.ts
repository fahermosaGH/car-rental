import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RequisitosAlquilerComponent } from './requisitos-alquiler';

describe('RequisitosAlquilerComponent', () => {
  let component: RequisitosAlquilerComponent;
  let fixture: ComponentFixture<RequisitosAlquilerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [RequisitosAlquilerComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(RequisitosAlquilerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
