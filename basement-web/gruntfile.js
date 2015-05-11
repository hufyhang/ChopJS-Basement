module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    connect: {
      server: {
        options: {
          port: 8090,
          base: '.'
        }
      }
    },

    open: {
      dev: {
        path: 'http://localhost:8090'
      }
    },

    watch: {
      all: {
        files: ['*.html', 'css/*.css', 'js/*.js'],
        options: {
          livereload: true
        }
      }
    }

  });

  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-open');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('serve', ['connect', 'open', 'watch']);

};
