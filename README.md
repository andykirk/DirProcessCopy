DirProcessCopy
==============

This class crawls an input director structure and copies them to an output folder.
If a Handler is specifed for a given file type, files of that type are processed by the handler.
If the handler returns `false` the file is NOT copied (though the handler may create a different file in the output folder).

Example, using the Twig handler (see sepereate repo) DirProcessCopy will take Twig files from an input dir and put rendered HTML files in the output dir.
