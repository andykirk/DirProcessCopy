DirProcessCopy
==============

DirProcessCopy (DPC) takes a config array that specifies input, output, tmp and processing paths, as well as other settings such as 'excludes'.
When run, DPC will look for viable files in the input directory, and copy them to the output directory.

At it's most basic this class can be used simply to copy files and folders from one place to another, ignoring certain files and folders.

Handlers
--------

Handlers are registered through a plugin system and add a layer of processing to the copy action.
In order to safely run these handlers, files and folders are processed into a 'processing' folder and copied to the output folder from there when all handlers have run.

The primary use case for this was to take a folder structure of Twig files, and copy them as rendered HTML to an output folder, as part of a PHP-based Static Site Generator.
However, it's still useful on it's own, and with more handler plugins, other processes could be run.

Handers are installed using Composer, and specified in the config as a list using a naming convention. E.g. the Twig handler would be listed in the config simply as 'Twig' and would resolve to 'DirProcessCopyHandlerTwig'.

If a Handler is specified for a given file type, files of that type are processed by the handler.
If the handler returns `false` the file is NOT copied (though the handler may create a different file in the output folder).

Example, using the Twig handler (see separate repo) DirProcessCopy will take Twig files from an input dir and put rendered HTML files in the output dir.