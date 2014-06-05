
include $(utildir)/PubRule

exdir=$(pubdir)/$(appname)/examples

pages_js = $(patsubst %.js,$(exdir)/%.js,$(wildcard *.js))
pages_css = $(patsubst %.css,$(exdir)/%.css,$(wildcard *.css))
pages_html = $(patsubst %.html,$(exdir)/%.html,$(wildcard *.html))

$(exdir):
	mkdir -p $@

$(exdir)/%.js: %.js $(exdir)
	cp $< $@
$(exdir)/%.css: %.css $(exdir)
	cp $< $@
$(exdir)/%.html: %.html $(exdir)
	cp $< $@

publish: $(exdir) $(pages_js) $(pages_css) $(pages_html) 
