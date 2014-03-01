# rada.gov.ua parser

## Installation

```bash
git clone git@github.com:makasim/rada-gov-ua-parser.git
cd rada-gov-ua-parser
composer install
```

## Usage

### Parse

To parse all laws accepted on 1996-06-24 do:

```bash
./bin/rada parse "1996-06-24"
```

or yestraday's laws:

```
./bin/rada parse "now - 1 day"
```

### Convert to markdown

```bash
./bin/rada convert-md "/path/to/html" "/path/to/md"
```

## Licence

License [MIT](LICENSE)
