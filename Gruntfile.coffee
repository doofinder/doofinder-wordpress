module.exports = (grunt) ->
  require("load-grunt-tasks")(grunt)

  grunt.initConfig
    clean:
      build: ['build/trunk/**/*', 'build/assets/**/*']

    copy:
      source:
        expand: true
        cwd: "doofinder-for-wordpress"
        src: ["**/*", "!**/*.scss"]
        dest: "build/trunk"
      assets:
        expand: true
        cwd: "assets"
        src: ["**/*"]
        dest: "build/assets"

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
      source:
        options:
          archive: "doofinder-for-wordpress.zip"
        files: [{
          expand: true
          src: ["doofinder-for-wordpress/**/*"]
          dest: "/"
        }]

  grunt.registerTask "build", ["clean", "copy", "compress"]
  grunt.registerTask "release", ["version", "build"]
  grunt.registerTask "default", ["build"]
