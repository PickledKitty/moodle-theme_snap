import {Pipe, PipeTransform} from '@angular/core';
import {Observable} from 'rxjs';
import {StringService} from './string.service';

/**
 * A Moodle language string in a template: {{ 'viewmore' | moodleString | async }}, or with its component:
 * {{ 'close' | moodleString:'moodle' | async }}. Strings are Snap's (theme_snap) unless a component is given.
 */
@Pipe({
  name: 'moodleString',
  standalone: true,
})
export class MoodleStringPipe implements PipeTransform {
  constructor(private strings: StringService) {
  }

  transform(key: string, component?: string): Observable<string> {
    return this.strings.getString$(key, component);
  }
}
