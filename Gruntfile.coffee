module.exports = (grunt) ->
  require("load-grunt-tasks")(grunt)

  grunt.initConfig
    clean:
      build: ['build/*']

    copy:
      build:
        expand: true
        cwd: "doofinder-for-wordpress"
        src: ["**/*", "!**/*.scss"]
        dest: "build"

    version:
      code:
        src: ["doofinder-for-wordpress/doofinder-for-wordpress.php"]
      text:
        options:
          prefix: 'Version: '
        src: [
          "doofinder-for-wordpress/doofinder-for-wordpress.php",
          "doofinder-for-wordpress/readme.txt"
        ]

    compress:
      build:
        options:
          archive: "doofinder-for-wordpress.zip"
        files: [{
          expand: true
          src: ["doofinder-for-wordpress/**/*"]
          dest: "/"
        }]

  grunt.registerTask "default", ["clean", "copy", "compress"]
  grunt.registerTask "release", ["version", "build"]
