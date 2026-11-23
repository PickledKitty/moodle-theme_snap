import {Injectable} from '@angular/core';
import {from, Observable} from 'rxjs';
import {shareReplay} from 'rxjs/operators';

/**
 * Moodle language strings, through Moodle's own core/str module (loaded with RequireJS on every Moodle page).
 * Each string is fetched once and shared. Off a Moodle page, or if the string can't be found, the key is shown.
 */
@Injectable({
  providedIn: 'root'
})
export class StringService {
  private strings = new Map<string, Observable<string>>();

  getString$(key: string, component = 'theme_snap'): Observable<string> {
    const id = `${component}/${key}`;
    if (!this.strings.has(id)) {
      const text = new Promise<string>(resolve => {
        const requirejs = (window as any).require;
        if (typeof requirejs !== 'function') {
          resolve(key);
          return;
        }
        requirejs(['core/str'], (str: any) => {
          const get = str.getString || str.get_string;
          Promise.resolve(get(key, component)).then(resolve, () => resolve(key));
        }, () => resolve(key));
      });
      this.strings.set(id, from(text).pipe(shareReplay(1)));
    }
    return this.strings.get(id)!;
  }
}
