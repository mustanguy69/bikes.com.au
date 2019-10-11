var gulp          = require('gulp'),
    less          = require('gulp-less'),
    sass          = require('gulp-sass'),
    concat        = require('gulp-concat'),
    uglify        = require('gulp-uglifyjs'),
    cssnano       = require('gulp-cssnano'),
    rename        = require('gulp-rename'),
    del           = require('del'),
    imagemin      = require('gulp-imagemin'),
    pngquant      = require('imagemin-pngquant'),
    cache         = require('gulp-cache'),
    autoprefixer  = require('gulp-autoprefixer'),
    browserSync   = require('browser-sync');

gulp.task('sass', function () {
    return gulp.src('tv_bikeworld2/sass/*.scss')
        .pipe(sass())
        .pipe(autoprefixer(['last 15 versions', '> 1%', 'ie 8', 'ie 7'], {cascade: true}))
        .pipe(gulp.dest('tv_bikeworld2/css'))
        .pipe(gulp.dest('../Magento_Theme/web/css'))
        .pipe(browserSync.reload({stream:true}))
});
/* Min CSS */
gulp.task('min-css', ['sass'], function () {
	return gulp.src('dist/css/custom.css')
		.pipe(cssnano())
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('dist/css'));
});
/* Browser reloader */
gulp.task('browser-sync', function () {
    browserSync({
        server: {
            baseDir: 'tv_bikeworld2'
        },
        notify: false
    });
});
/* IMG */
gulp.task('img', function () {
    return gulp.src('tv_bikeworld2/images/**/*')
        .pipe(cache(imagemin({
            interlaced:true,
            progressive:true,
            svgoPlugins:[{removeViewBox:false}],
            use:[pngquant()]
        })))
        .pipe(gulp.dest('dist/images'));
});
/* Watcher */
gulp.task('watch', ['browser-sync', 'min-css'], function () {
    gulp.watch('tv_bikeworld2/sass/**/*.scss', ['sass']);
    gulp.watch('tv_bikeworld2/*.html', browserSync.reload);
});