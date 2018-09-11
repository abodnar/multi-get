# Adam's Multi-Get

## Setup

```bash
$ php composer.phar install
```

## Usage

```bash
php multi-get.php [options] <url> [operands]

Operands:
  <url>  URL to download file from

Options:
  -?, --help               Show this help and quit
  -v, --verbose            Display verbose output
  -o, --output [<arg>]     Filename to save download to
  -n, --numchunks [<arg>]  Number of chunks to download. 0 to use as many as
                           needed
  -s, --chunksize [<arg>]  Size of chunks to download in bytes.
  -t, --totalsize [<arg>]  Total size to download in bytes
```