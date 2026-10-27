import { Component, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';

@Component({
  imports: [MatDialogModule,MatButtonModule, MatIconModule],
  selector: 'app-frm-dialogo-general',
  styleUrl: './frm-dialogo-general.css',
  templateUrl: './frm-dialogo-general.html',
})
export class FrmDialogoGeneral {
  readonly data = inject<any>(MAT_DIALOG_DATA);
}
