import sys
from pushbullet import Pushbullet
pb = Pushbullet('[Your_API_HERE]')
torrentname =  ' '.join(sys.argv[1:])
push = pb.push_note('Transmission: Added New Torrent',torrentname)
