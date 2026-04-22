import resolve from '@rollup/plugin-node-resolve';
import terser from '@rollup/plugin-terser';

const typo3Exports = [
  'autosize',
  'bootstrap',
  'broadcastchannel.js',
  'cropperjs',
  'd3-dispatch',
  'd3-drag',
  'd3-selection',
  'ev-emitter',
  'imagesloaded',
  'interactjs',
  'jquery',
  '@lit/reactive-element',
  'lit',
  'lit-element',
  'lit-html',
  'moment',
  'moment-timezone',
  'nprogress',
  'sortablejs',
  'tablesort.dotsep.js',
  'tablesort',
  'taboverride',
];
const typo3Prefixes = [
  '@typo3/',
  '@lit/reactive-element/',
  'lit/',
  'lit-element/',
  'lit-html/',
  'flatpickr/',
];

export function typo3Resolve() {
  return {
    name: 'typo3Resolve',
    resolveId(id) {
      const external = true
      for (const exportName of typo3Exports) {
        if (id === exportName) {
          return { id, external }
        }
      }
      for (const exportPrefix of typo3Prefixes) {
        if (id.startsWith(exportPrefix)) {
          if (!id.endsWith('.js')) {
            id += '.js'
          }
          return { id, external }
        }
      }
    }
  }
}

export default {
  input: [
    './Build/src/AlternativeGenerator.js',
    './Build/src/CreditsViewHelper.js',
    './Build/src/AjaxMetaGenerate.js',
    './Build/src/ContextMenuActions.js',
  ],
  output: [
    {
      dir: './Resources/Public/JavaScript/ECMAScript6',
      format: 'es',
      chunkFileNames: '[name].js',
      plugins: [terser()]
    },
  ],
  plugins: [
    typo3Resolve(),
    resolve({
      mainFields: ['module', 'main'],
      modulesOnly: true
    }),
  ],
}
