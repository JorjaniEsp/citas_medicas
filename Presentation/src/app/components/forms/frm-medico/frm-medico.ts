import { Component, effect, inject, OnInit, signal } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatDividerModule } from '@angular/material/divider';
import { MatAnchor, MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { form, FormField, FormRoot, maxLength, minLength, pattern, required, RootFieldContext, SchemaPath, validate } from '@angular/forms/signals';
import { MedicoCreateInput } from '../../../shared/models/medico-model';

function matchPattern(field: SchemaPath<string>, regex: RegExp, message: string, optional = false) {
  return validate(field, (context: RootFieldContext<string>): { kind: string; message: string } | null => {
    const value = context.value();
    return (optional && !value) || regex.test(value) ? null : { kind: 'pattern', message };
  });
}

@Component({
  imports: [MatDialogModule, MatDividerModule, MatAnchor, MatButtonModule, MatFormFieldModule, MatInputModule,
    FormRoot, FormField
  ],
  selector: 'app-frm-medico',
  styleUrl: './frm-medico.css',
  templateUrl: './frm-medico.html',
})

export class FrmMedico implements OnInit {

  titulo: string = "Formulario Medico";
  dialogRef = inject(MatDialogRef<FrmMedico>)
  private readonly dialog = inject(MatDialog);
  private readonly data   = inject(MAT_DIALOG_DATA);

  readonly isEditing = this.data.data !== undefined;

  objMedico = signal<MedicoCreateInput>({
    especialidad_id: 0,
    nombre_completo: '',
    telefono: '',
    username: '',
    licencia: '',
    password: ''
  });

  frmMedico = form(this.objMedico, (s) => {
    validate(s.especialidad_id, (context: RootFieldContext<number>): { kind: string; message: string } | null => {
      const numericValue = Number(context.value());
      if (isNaN(numericValue) || !Number.isInteger(numericValue) || numericValue <= 0) {
        return { kind: 'invalid', message: 'Especialidad inválida' };
      }
      return null;
    });

    if (!this.isEditing){
      required(s.username!, {message: "El nombre de usuario es requerido"});
      minLength(s.username!, 8, {message: "El usuario debe tener al menos 8 caracteres"});
      maxLength(s.username!, 16, {message: "El usuario no puede tener más de 16 caracteres"});
      matchPattern(s.username!, /^[a-z0-9]{8,16}$/, "Formato de usuario inválido");
  
      required(s.password!, {message: "La contraseña es requerida"});
      minLength(s.password!, 8, {message: "La contraseña debe tener al menos 8 caracteres"});
      maxLength(s.password!, 16, {message: "La contraseña no puede tener más de 16 caracteres"});
      matchPattern(s.password!, /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#$@*])[\s\S]{8,16}$/,
        "La contraseña debe incluir mayúscula, minúscula, número y un símbolo (#$@*)"); 
    }


    required(s.nombre_completo, {message: "El nombre es requerido"});
    minLength(s.nombre_completo, 11, {message: "El nombre debe tener al menos 11 caracteres"});
    maxLength(s.nombre_completo, 150, {message: "El nombre no puede tener más de 150 caracteres"});
    matchPattern(s.nombre_completo, /^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/u,
      "Formato de nombre inválido");

    pattern(s.telefono!, /^[2-9][0-9]{3}-[0-9 ]{4}$/, { message: 'Formato de teléfono inválido' });

    required(s.licencia, {message: "La licencia es requerida"});
    minLength(s.licencia, 8, {message: "La licencia debe tener al menos 8 caracteres"});
    maxLength(s.licencia, 50, {message: "La licencia no puede tener más de 50 caracteres"});
  }, {
    submission: {
      action: async () => {
        this.dialogRef.close(this.objMedico());
      }
    }
  });

  ngOnInit(): void {
    this.titulo = this.data.title;
    if (this.data.data) {
      this.objMedico.set({
        especialidad_id: this.data.data.especialidad_id,
        nombre_completo: this.data.data.nombre_completo,
        licencia: this.data.data.licencia,
        telefono: this.data.data.telefono ?? '',
        username: '',
        password: '',
      });
    }
  }

  /*constructor() {
    effect(() => {
      console.log({
        especialidad: this.frmMedico.especialidad_id().errors(),
        nombre: this.frmMedico.nombre_completo().errors(),
        licencia: this.frmMedico.licencia().errors(),
        telefono: this.frmMedico.telefono!().errors(),
        username: this.frmMedico.username!().errors(),
        password: this.frmMedico.password!().errors(),
      });
    });
  }
  */

}

