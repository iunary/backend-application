import { Component, OnInit, IterableDiffer, IterableDiffers, DoCheck } from '@angular/core';

import { Task } from '../../../models/task.model';

import { DashboardService } from '../dashboard.service';
import { ApiService } from '../../../api/api.service';

import * as moment from 'moment';

@Component({
    selector: 'dashboard-tasklist',
    templateUrl: './tasks.list.component.html'
})
export class TaskListComponent implements OnInit, DoCheck {
    itemsArray: Task[] = [];
    itemsDiffer: IterableDiffer<Task>;
    totalTime = 0;

    _filter: string|Task = '';
    filteredItems: Task[] = [];

    get totalTimeStr(): string {
        const duration = moment.duration(this.totalTime, 'seconds');
        const hours = Math.floor(duration.asHours());
        const minutes = Math.floor(duration.asMinutes()) - 60 * hours;
        const hoursStr = hours > 9 ? '' + hours : '0' + hours;
        const minutesStr = minutes > 9 ? '' + minutes : '0' + minutes;
        return `${hoursStr}:${minutesStr}`;
    }

    constructor(
        protected api: ApiService,
        protected dashboardService: DashboardService,
        differs: IterableDiffers,
    ) {
        this.itemsDiffer = differs.find(this.itemsArray).create();
    }

    ngOnInit() {
        this.reload();
    }

    ngDoCheck() {
        const itemsChanged = this.itemsDiffer.diff(this.itemsArray);
        if (itemsChanged) {
            this.totalTime = this.itemsArray
                .map(task => moment.duration(task.total_time))
                .reduce((total, duration) => total + duration.asSeconds(), 0);
        }
    }

    setTasks(result) {
        this.itemsArray = result;
        this.filter();
    }

    reload() {
        const user: any = this.api.getUser() ? this.api.getUser() : null;
        const params = {
            'user_id': user.id,
            'with': 'project'
        };
        this.dashboardService.getTasks(this.setTasks.bind(this), params);
    }

    filter(filter: string|Task = this._filter) {
        this._filter = filter;
        this.filteredItems = this.itemsArray.filter(task => {
            if (typeof this._filter === "string") {
                const filter = this._filter.toLowerCase();
                const taskName = task.task_name.toLowerCase();
                const projName = task.project
                    ? task.project.name.toLowerCase() : '';
                return taskName.indexOf(filter) !== -1
                    || projName.indexOf(filter) !== -1;
            } else {
                return +task.id === +this._filter.id;
            }
        });
    }
}
