import {firstValueFrom, of} from "rxjs";
import {StringService} from "./string.service";

export class MockStringService {
  getString$(key: string, component?: string) {
    return of(key);
  }
}

describe('StringService', () => {
  const realRequire = (window as any).require;

  afterEach(() => {
    (window as any).require = realRequire;
  });

  it('shows the key when Moodle\'s RequireJS isn\'t on the page', async () => {
    (window as any).require = undefined;
    const text = await firstValueFrom(new StringService().getString$('viewmore'));
    expect(text).toBe('viewmore');
  });

  it('asks core/str for the string, in theme_snap unless a component is given', async () => {
    const getString = jasmine.createSpy('getString').and.callFake((key: string, component: string) =>
      Promise.resolve(`${component}:${key}`));
    (window as any).require = (modules: string[], callback: (str: object) => void) => callback({getString});
    const service = new StringService();
    expect(await firstValueFrom(service.getString$('viewmore'))).toBe('theme_snap:viewmore');
    expect(await firstValueFrom(service.getString$('close', 'moodle'))).toBe('moodle:close');
    // Fetched once and shared.
    await firstValueFrom(service.getString$('viewmore'));
    expect(getString).toHaveBeenCalledTimes(2);
  });
});
