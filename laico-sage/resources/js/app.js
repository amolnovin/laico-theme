/**
 * Laico Design — Primary Frontend Entrypoint (`resources/js/app.js`).
 */

import './modules/main.js';
import './modules/motion.js';
import './modules/projects.js';
import './modules/project.js';

// Register static theme imagery with Vite's asset manifest
import.meta.glob([
  '../images/**',
  '../fonts/**',
]);
