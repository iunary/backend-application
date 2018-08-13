import {NgModule} from '@angular/core';
import {UsersFiltersComponent} from './filters/users/users.filters.component';
import {ProjectsFiltersComponent} from './filters/projects/projects.filters.component';
import {NgSelectModule} from '@ng-select/ng-select';
import {CommonModule} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {ProjectsService} from './pages/projects/projects.service';
import {DualListComponent} from 'angular-dual-listbox';
import {TranslateModule} from '@ngx-translate/core';

@NgModule({
    imports: [
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        NgSelectModule,
        TranslateModule,
    ],
    declarations: [
        UsersFiltersComponent,
        ProjectsFiltersComponent,
        DualListComponent
    ],
    exports: [
        CommonModule,
        FormsModule,
        NgSelectModule,
        ReactiveFormsModule,
        UsersFiltersComponent,
        ProjectsFiltersComponent,
        DualListComponent
    ],
    providers: [
        ProjectsService
    ]
})

export class SharedModule {}
