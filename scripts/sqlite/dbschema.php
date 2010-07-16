create table content (
 	id INTEGER PRIMARY KEY ASC,
	filename varchar(255),
	category varchar(30),
	timeslot varchar(30),
	statistics_last_played datetime,
	statistics_playcount integer,
	metadata_title vachar(255),
	metadata_duration_in_seconds integer
);