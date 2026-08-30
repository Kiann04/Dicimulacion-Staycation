/**
 * Utility to combine conditional class names safely without external dependencies.
 */
export type ClassValue =
  | string
  | number
  | boolean
  | undefined
  | null
  | { [key: string]: unknown }
  | ClassValue[];

export function cn(...inputs: ClassValue[]): string {
  const classes: string[] = [];

  function process(input: ClassValue) {
    if (!input) return;

    if (typeof input === 'string' || typeof input === 'number') {
      classes.push(String(input).trim());
    } else if (Array.isArray(input)) {
      for (const item of input) {
        process(item);
      }
    } else if (typeof input === 'object') {
      for (const [key, val] of Object.entries(input)) {
        if (val && key) {
          classes.push(key.trim());
        }
      }
    }
  }

  for (const input of inputs) {
    process(input);
  }

  return classes.filter(Boolean).join(' ');
}
