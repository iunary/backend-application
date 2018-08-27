<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Models\ProjectsUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Nwidart\Modules\Collection;

class ProjectReportController extends Controller
{
  /**
   * Handle the incoming request.
   *
   * @param  \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    $type = $request->input('type');
    $start_at = $request->input('start_at');
    $end_at = $request->input('end_at');
    return $this->$type($start_at, $end_at);
  }

  /**
   * [resources description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function resources($uids, $pids, $start_at, $end_at)
  {
    $projects = new Collection();
    foreach ($pids as $pid) {
      $project = Project::with(['users' => function ($q) use ($start_at, $end_at, $uids, $pid) {
        return $q->with(['tasks' => function ($q) use ($start_at, $end_at, $pid) {
          return $q->with(['timeIntervals' => function ($q) use ($start_at, $end_at) {
            if ($end_at === '') {
              $q = $q->where('start_at', $start_at);
            } else {
              $q = $q->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]]);
            }
            return $q
              ->select('task_id')
              ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) duration')
              ->groupBy('task_id');
          }])->where('project_id', $pid)->select('id', 'project_id', 'user_id', 'task_name');
        }])->select('full_name', 'id', 'avatar')
          ->whereIn('id', $uids);
      }])
        ->whereHas('users', function ($q) use ($start_at, $end_at, $uids, $pid) {
          return $q->whereHas('tasks', function ($q) use ($start_at, $end_at, $pid) {
            return $q->whereHas('timeIntervals', function ($q) use ($start_at, $end_at) {
              if ($end_at === '') {
                $q = $q->where('start_at', '=', $start_at);
              } else {
                $q = $q->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]]);
              }
              return $q;
            })->where('project_id', $pid);
          })->whereIn('id', $uids);
        })
        ->select('id', 'name')
        ->where('id', $pid)
        ->get();
      if ($project->count() > 0) {
        $projects->push($project->first());
      }
    }
//    $projects = Project::query()
//      ->whereIn('id', $pids)
//      ->select('id', 'name');
//    $projects = $projects->get()->map(function ($project) {
//      $project->load(['users' => function ($q) use ($project) {
//        return $q->select('id', 'full_name', 'avatar')
//          ->with(['tasks' => function ($q) use ($project) {
//            return $q->where('project_id', $project->id)
//              ->select('id', 'project_id', 'task_name', 'user_id');
//          }])
//          ->whereHas('tasks', function ($q) use ($project) {
//            return $q->where('project_id', $project->id);
//          });
//      }]);
//      $project->filter(function ($project){
//
//      });
//    });
//    $projects = $projects->map(function ($project) {
//      $users = $project->users->filter(function ($user){
//        return $user->id === 1;
//      });
//      return $users;
//    });
//    dd($projects->toArray());
    $projects = $projects->map(function ($project) {
      $project_time = 0;
      $project->users = $project->users->map(function ($user) use (&$project_time) {
        $tasks_time = 0;
        $user->tasks = $user->tasks->map(function ($task) use (&$tasks_time) {
          $tasks_time += $task->timeIntervals[0]->duration;
          $task['duration'] = $task->timeIntervals[0]->duration;
//          $task->forget('timeIntervals');
          return $task;
        });
        $user['tasks_time'] = $tasks_time;
        $project_time += $tasks_time;
        return $user;
      });
      $project['project_time'] = $project_time;
      return $project;
    });
    $projects = $projects->map(function ($project) {
      $p = new Collection($project);
      $p['users'] = $project->users->filter(function ($user) {
        return $user->tasks->count() > 0;
      });
      return $p;
    });
    dd($projects->toArray());
    dd($projects);
    return $projects;
  }

  /**
   * [events description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function report(Request $request)
  {
    $start_at = $request->input('start_at') == null ? '' : $request->start_at;
    $end_at = $request->input('end_at') == null ? '' : $request->end_at;
    $uids = $request->uids;
    $pids = $request->pids;
    return response()->json($this->resources($uids, $pids, $start_at, $end_at));
  }

  public function projects(Request $request)
  {
    $uids = $request->uids;
    $projects = ProjectsUsers::query()
      ->with(['project' => function ($q) {
        return $q->select('id', 'name');
      }])
      ->whereIn('user_id', $uids)
      ->get()
      ->unique('project')
      ->pluck('project');
    return response()->json($projects);
  }
}
