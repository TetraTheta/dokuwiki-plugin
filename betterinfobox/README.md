# Better Infobox Plugin for DokuWiki

A DokuWiki syntax plugin that provides structured infoboxes with **full DokuWiki syntax support** inside key and value text. Unlike the original `infobox` plugin, you can use wiki formatting, plugin syntax (e.g. `<html>` for ruby tags), links, and images inside any field.

## Installation

Copy the `betterinfobox` folder into `lib/plugins/` of your DokuWiki installation:

```
lib/plugins/betterinfobox/
  plugin.info.txt
  syntax.php
  style.css
  script.js
```

## Syntax

```
<infobox [optional-css-class]>
type | key text = value text
...
</infobox>
```

Each line follows the pattern:

```
<type>[| <key text>][ = <value text>]
```

- **`|`** separates the type from the key/value portion.
- **` = `** (space-equals-space) separates the key from the value.
- Both key text and value text support full DokuWiki syntax and plugin syntax.

## Types Reference

| Type       | Usage                                    | Description                            |
| ---------- | ---------------------------------------- | -------------------------------------- |
| `title`    | `title \| Title Text`                    | Infobox title row (centered, bold)     |
| `banner`   | `banner \| namespace:image.jpg`          | Full-width banner image                |
| `image`    | `image \| namespace:image.jpg`           | Centered image with optional caption   |
|            | `image \| namespace:image.jpg = Caption` |                                        |
| `tab`      | `tab \| namespace:image.jpg = Label`     | Tabbed image (group consecutive lines) |
| `section`  | `section \| Section Name`                | Section header                         |
| `collapse` | `collapse \| Section Name`               | Collapsible section header (closed)    |
| `text`     | `text \| Label = Value`                  | Key-value row                          |
| `wide`     | `wide \| Full-width content`             | Full-width row spanning both columns   |
| `divider`  | `divider` or `divider \| Text`           | Horizontal divider with optional text  |

## Spoiler

Prefix key text or value text with `!` to make it a spoiler (hidden until hover/click):

```
text | Codename = !Secret Identity
text | !Hidden Label = !Hidden Value
```

## Tabbed Images

Place consecutive `tab` lines to create a tabbed image panel:

```
tab | char_front.jpg = Front
tab | char_back.jpg = Back
tab | char_profile.jpg = Profile
```

## Example

```
<infobox>
title | This is title
section | Section 1
image | https://placehold.co/400
section | Section 2
text | Field 1 = <html><ruby>明日<rp>(</rp><rt>Ashita</rt><rp>)</rp></ruby></html>
text | Field 2 = Using <nowiki>\\ </nowiki>\\ for line breaking is supported
</infobox>
```

Note that `\\ ` is DokuWiki's line break syntax (equivalent to `<br />`), and `<html>...</html>` is DokuWiki's inline HTML embedding (requires `htmlok` plugin).

## Customization

Add a CSS class to `<infobox>` for per-instance styling:

```
<infobox character-box>
...
</infobox>
```

Then in your DokuWiki user CSS (`conf/userstyle.css`):

```css
.bib-infobox.character-box {
  width: 320px;
  border-color: #5b92c8;
}

.bib-infobox.character-box .bib-title {
  background: #5b92c8;
  color: #fff;
}
```
