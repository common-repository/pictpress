FILES = pictpress/pictpress.php \
        pictpress/pictpress-archive-pages.php \
	pictpress/pictpress.css \
        pictpress/class.image.php \
        pictpress/class.imagedir.php \
	pictpress/edit-subtitles.php \
	pictpress/edit-subtitles-1.2.php \
	pictpress/options.php \
	pictpress/license.txt \
        pictpress/resize.php \
	pictpress/settings.php \
	pictpress/upgrade.php \
	pictpress/wp15.php \
        pictpress/images/shadow.gif \
        pictpress/images/shadow.png \
        pictpress/images/shadow2.gif \
        pictpress/images/shadow2.png \
        pictpress/readme.html

# Create zip file with pictpress directory
#
pictpress.zip : $(FILES)
	zip pictpress.zip $(FILES)

clean:
	rm pictpress.zip
