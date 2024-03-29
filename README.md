Renoir Engine Mark VI.
======================

# Archiving:

As of December of 2021 I am archiving this repository. The Renoir engine has powered many applications along these years, but it is time to retire it from development and adopt new, more modular approaches. 

# About

This is my very own framework (more like... set of tools) for developing PHP applications. The legacy code is actually very old, but I am working very hard on creating a new version.

This is the "development" version: it should not be used with legacy applications. Of course, I don't endorse you using this for your own projects. Go check some other big frameworks that are peer-reviewed and tested by hundreds of thousands every day. This I do for my own amusement and use.

# Why Mark VI?

The previous versions of the "framework" were called "the engine" until I decided to name it Renoir Engine so sort of matches my domain name. Anyway, there have been several incarnations of the tools that have been sequentially named Mark I, Mark II... I think the last was Mark IV but given the differences between the last incarnation and this, I am skipping a number.

# Differences and missing stuff from the previous version.

The code is NOT compatible between versions.

As for notable differences, there are a few:
		-Everything is new. Oh well.
		-Everything is namespaced now and I am trying to make an effort to keep things modular.
		-A large part of the previous version were wrappers around mysql_* functions, to make them secure. These are discarded and PDO use is directly expected.
		-The language: everything is in English now.
		-Relationship abstraction between tables dissapear for now. I kind of always figured that M to N relationships were overkill for the previous version.
		-Exception: each set of tools (each namespace) throws its own exception now.
		-The base email class will (probably) be retired. The days of EHLO and talking TTLS are gone :(.
		-Many things go to the trash, like the HTML tools, a variable container or the GD image tools. Perhaps some will be replaced as I need them.

# How to use:

The "examples" directory will contain "how to"s for every piece. 

In addition, you can generate the Doxygen documentation by running "doxygen doxygen.config" from the root (provided you installed Doxygen before, of course). It will not contain examples but will show each and every class with a definition and a fully commented public interface.

# What's all that stuff in the src directory?

If it is NOT another directory, it is a part of the previous version. I am keeping them around, just in case I need them but so far all code has been brand new.

# What's with the RET namespace?

The root namespace began its existence as "Renoir_engine". As the project grows I am reminded more and more of my C++ background, where I absolutely loath long namespace names, thus I came up with RET as the acronym for Renoir Engine Toolkit.

# Why don't you follow the PSR naming conventions?

Because I am a rebel. 

No, seriously, some of the recommendations I don't like, so as much as I am aware of them, I prefer not to follow. According to the PHP-FIG itself, that's OK ("Nobody in the group wants to tell you, as a programmer, how to build your application.") but it's not like I asked them.

Actually, now that I got you reading, I am firmly believe that the only naming convention that matters is "be consistent" within the confines of you project. That's all.

# Parts: 

A description of the Renoir Engine Mark IV parts follows:

## ORM:

A lightweight ORM layer, consisting on a very simple connection wrapper, a repository of database entity descriptions and a base class for all database entity classes.

TODO: Create the database_entity_sql class... Which actually works as kind of a repository in doctrine, now that I think about it.

## View:

A lightweight templating engine complete with its own script syntax and variable scope resolution systems.

## Tools:

Miscellaneous tools:
	- Wrapper for INI files.
	- TODO: Pagination.
	- TODO: i16n.
	- TODO: Tooolkit.

## File:

TODO: Miscellaneous filesystem operations.

## Email:

TODO: Base class for email... perhaps I will phase it out and use PHPMailer.


