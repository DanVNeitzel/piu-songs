"""Align physical channel folders; preview by default, use --apply to write."""
import argparse
import json
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parent
SONGS = ROOT / 'Songs'
MERGES = {
    '08-1ST~PERF': '08-1ST~ZERO',
    '09-EXTRA~PREX3': '08-1ST~ZERO',
    '11-EXCEED~ZERO': '08-1ST~ZERO',
    '14-FIESTA': '16-FIESTA~FIESTA2',
    '15-FIESTA EX': '16-FIESTA~FIESTA2',
    '16-FIESTA 2': '16-FIESTA~FIESTA2',
}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true')
    parser.add_argument('--artwork', type=Path, required=True,
                        help='Directory containing the original channel PNGs')
    args = parser.parse_args()
    config = json.loads((ROOT / 'channels.json').read_text(encoding='utf-8'))
    moves = []
    destinations = set()
    for old, new in MERGES.items():
        source = SONGS / old
        target = SONGS / new
        if not source.exists():
            continue
        for child in source.iterdir():
            dest = target / child.name
            # Verify the complete destination remains inside this workspace.
            dest.resolve().relative_to(SONGS.resolve())
            if dest.exists() or dest in destinations:
                raise RuntimeError(f'Destination conflict: {dest}')
            destinations.add(dest)
            moves.append((child, dest))
    for channel in config['channels']:
        if not (args.artwork / channel['artwork']).is_file():
            raise FileNotFoundError(channel['artwork'])
    print(f'{len(moves)} items to move; {len(config["channels"])} channel folders')
    if not args.apply:
        for old, new in MERGES.items():
            print(f'{old} -> {new}')
        return
    for source, dest in moves:
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.move(str(source), str(dest))
    for old in MERGES:
        source = SONGS / old
        if source.is_dir():
            source.rmdir()  # Only remove the now-empty directory.
    for channel in config['channels']:
        folder = SONGS / channel['folder']
        folder.resolve().relative_to(SONGS.resolve())
        folder.mkdir(exist_ok=True)
        target = folder / 'banner.png'
        artwork = args.artwork / channel['artwork']
        if target.exists() and target.read_bytes() != artwork.read_bytes():
            backup = folder / 'banner_legacy.png'
            if backup.exists():
                raise RuntimeError(f'Banner backup already exists: {backup}')
            shutil.copy2(target, backup)
        shutil.copy2(artwork, target)
    print('Channel folders and banners updated.')


if __name__ == '__main__':
    main()
