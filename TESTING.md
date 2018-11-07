# Testing

For testing purposes there is a file `pages.xml` that can be loaded by calling `composer import` in the LangCodeOverride root directory, or by using `Special:Import`. It might be necessary to give yourself additional rights to do that, but in a default Vagrant setup the “Admin” user will have the necessary rights.

The page `PAGELIST` holds the pages to be exported, and to do the actual export and rebuild the file `pages.xml`, call `composer import`, in the LangCodeOverride root directory. It is also possible to do the same by manually copy-pasting `PAGELIST` into `Special:Import`. In this case you must change the name of the exported file if you want to reimport it with `composer import`.

After importing the pages, the “Main Page” should give you a few additional links, one going to a page of test cases. This page links to predefined test cases for various purposes, especially to verify proper integration of the various parts. They are also working examples on how you can interact with the framework.
