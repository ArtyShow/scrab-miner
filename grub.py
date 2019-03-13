# grub web info. Python 3.5
# Autor: Romanov Artem
# Email: develop@romanov.one


import requests
from lxml import html
import sqlite3
import re

conn = sqlite3.connect("miners.db")
cursor = conn.cursor()

def is_available(host):
    """
    Returns True if host responds
    """
    try:
        sysinfo = requests.get('http://'+host+'/',
                               auth=requests.auth.HTTPDigestAuth('root', 'root'),
                               timeout = 1)
    except requests.exceptions.ConnectTimeout:
        return False
    else:
        return True
    

def get_stat (ipaddr):
    """ Get statistics from a host. May be 4 miners type.

    0 - unsupported device
    1 - antminer hwv 1.0.0.6 or 1.0.1.3 or 16.8.1.3
    2 - antminer hwv 1.0.0.9
    3 - innosilicon
    Return string with parameters

    """

    miners_device = {}
    miners_device['ip'] = ipaddr
    miners_device['status'] = 1
    miners_device['type'] = '-'
    miners_device['minertype'] = '-'
    miners_device['login'] = 'root'
    miners_device['password'] = 'root'
    miners_device['name'] = '-'
    miners_device['ghs5s'] = '-'
    miners_device['ghsavg'] = '-'
    miners_device['uptime'] = '0d0h0m0s'
    miners_device['pools'] = []
    miners_device['miners'] = []
    miners_device['fans'] = []
    print("Get stat for "+ipaddr)
    miner = 0
    try:
        #find type of miners type()
        sysinfo = requests.get('http://'+ipaddr+'/cgi-bin/get_system_info.cgi',
                               auth=requests.auth.HTTPDigestAuth('root', 'root'))
        if sysinfo.status_code==404:
            miner = 3
            sysinfo = requests.get('http://'+ipaddr+'/cgi-bin/api.py', 
                                   auth=requests.auth.HTTPDigestAuth('root', 'root'))
            if sysinfo.status_code==404:
                miner = 0
        elif sysinfo.json()['ant_hwv'] == '1.0.0.9':
            miner = 2
        elif sysinfo.json()['ant_hwv'] == '1.0.0.6' or sysinfo.json()['ant_hwv'] == '1.0.1.3'\
                or sysinfo.json()['ant_hwv'] == '16.8.1.3':
            miner = 1
        else:
            miner = 0
        miners_device['type'] = miner

        if miner == 1 or miner == 2:
            miners_device['name'] = sysinfo.json()['hostname']
            miners_device['minertype'] = sysinfo.json()['minertype']
            #sysinfo2 = requests.get('http://'+ipaddr+'/cgi-bin/get_miner_conf.cgi',
            #                        auth=requests.auth.HTTPDigestAuth('root', 'root'))
            #for item in sysinfo2.json()['pools']:
            #    pools = {}
            #    pools['url'] = item['url']
            #    pools['user'] = item['user']
            #    miners_device['pools'].append(pools)
        elif miner == 3:
            miners_device['name'] = 'NoName'
            miners_device['minertype'] = 'NotSet'
            ghs5s = 0
            ghsav = 0
            for item in sysinfo.json()['POOLS']:
                pools = {}
                pools['status'] = item['Status']
                pools['url'] = item['URL']
                pools['user'] = item['User']
                miners_device['pools'].append(pools)
            
            for item in sysinfo.json()['DEVS']:
                miners = {}
                miners['chain'] = 'ASC' + str(item['ASC'])
                miners['MHS'] = str(item['MHS av'])
                ghs5s += item['MHS 5s']
                ghsav += item['MHS av']
                miners['t_min'] = str(item['TempMIN'])
                miners['t_max'] = str(item['TempMAX'])
                miners['t_avg'] = str(item['TempAVG'])
                miners['status'] = item['Status']
                miners['ASIC'] = str(item['CHIP'])
                miners['t_PCB'] = '-'
                miners['t_chip'] = '-'
                miners['countX'] = 0
                miners['countO'] = 0
                miners_device['miners'].append(miners)

            miners_device['fans'].append({'speed': str(sysinfo.json()['DEVS'][0]['DUTY'])+'%'})
            tmp_uptime = int(sysinfo.json()['DEVS'][0]['Device Elapsed'])
            tmp_str_uptime = str(tmp_uptime // 86400) + 'd'
            tmp_uptime = tmp_uptime % 86400
            tmp_str_uptime = tmp_str_uptime + str(tmp_uptime // 3600) + 'h'
            tmp_uptime = tmp_uptime % 3600
            tmp_str_uptime = tmp_str_uptime + str(tmp_uptime // 60) + 'm' + str(tmp_uptime % 60) + 's'
            miners_device['uptime'] = tmp_str_uptime
            miners_device['ghs5s'] = str(ghs5s)
            miners_device['ghsavg'] = str(ghsav)

        if miner == 2:
            stat = requests.get('http://'+ipaddr+'/cgi-bin/get_status_api.cgi', 
                                auth=requests.auth.HTTPDigestAuth('root', 'root'))
            antminer_list = stat.text.split('|')[4].split(',')
            for item in antminer_list:
                if item.startswith('Elapsed'):
                    tmp_uptime = int(item.split('=')[1])
                    tmp_str_uptime = str(tmp_uptime // 86400) + 'd'
                    tmp_uptime = tmp_uptime % 86400
                    tmp_str_uptime = tmp_str_uptime + str(tmp_uptime // 3600) + 'h'
                    tmp_uptime = tmp_uptime % 3600
                    tmp_str_uptime = tmp_str_uptime + str(tmp_uptime // 60) + 'm' + str(tmp_uptime % 60) + 's'
                    miners_device['uptime'] = tmp_str_uptime
                if item.startswith('GHS 5s'):
                    miners_device['ghs5s'] = item.split('=')[1]
                if item.startswith('GHS av'):
                    miners_device['ghsavg'] = item.split('=')[1]
                if item.startswith('fan_num'):
                    num_fans = int(item.split('=')[1])
                if item.startswith('fan') and not item.startswith('fan_num'):
                    if(int(item.split('=')[0][-1])<=num_fans):
                        miners_device['fans'].append({'speed': item.split('=')[1]})
                if item.startswith('miner_count'):
                    num_miner = int(item.split('=')[1])
                    tmp_asic = []
                    i = 0
                    while i < num_miner:
                        miners_device['miners'].insert(i,{'chain': i+1,'t_min': '-','t_max': '-','t_avg': '-','status': '-'})
                        i += 1
                if item.startswith('temp') and not item.startswith('temp_'):
                    cur_num = int(item.split('=')[0][-1])
                    if(cur_num <= num_miner):
                        if item.startswith('temp2_'):
                            miners_device['miners'][cur_num - 1]['t_chip'] = item.split('=')[1]
                        else:
                            miners_device['miners'][cur_num - 1]['t_PCB'] = item.split('=')[1]
                if item.startswith('chain_rate'):
                    cur_num = int(item.split('=')[0][-1])
                    if(cur_num <= num_miner):
                        miners_device['miners'][cur_num - 1]['MHS'] = item.split('=')[1]
                if item.startswith('chain_acn'):
                    cur_num = int(item.split('=')[0][-1])
                    if(cur_num <= num_miner):
                        miners_device['miners'][cur_num - 1]['ASIC'] = item.split('=')[1]
                if item.startswith('chain_acs'):
                    cur_num = int(item.split('=')[0][-1])
                    if(cur_num <= num_miner):
                        miners_device['miners'][cur_num - 1]['countX'] = int(item.split('=')[1].lower().count('x'))
                        miners_device['miners'][cur_num - 1]['countO'] = int(item.split('=')[1].lower().count('o'))
            antminer_list_pools = stat.text.split('|')[5].split(',')
            for item in antminer_list_pools:
                if item.startswith('Msg'):
                    num_pools = int(item.split('=')[1][0])
            i = 0
            while i < num_miner:
                antminer_list_pool = stat.text.split('|')[6+i].split(',')
                tmp_pool = {}
                for item in antminer_list_pool:
                    tmp_pool['url'] = item.split('=')[1] if item.startswith('URL') else "-"
                    tmp_pool['user'] = item.split('=')[1] if item.startswith('User') else "-"
                    tmp_pool['status'] = item.split('=')[1] if item.startswith('Status') else "-"
                miners_device['pools'].append(tmp_pool)
                i += 1
            
        if miner == 1:
            stat = requests.get('http://'+ipaddr+'/cgi-bin/minerStatus.cgi', 
                                auth=requests.auth.HTTPDigestAuth('root', 'root'))
            page = html.document_fromstring(stat.content)

            miners_device['uptime'] = page.xpath('//*[@id="ant_elapsed"]')[0].text
            ghs5s = page.xpath('/html/body/div[3]/div[2]/div[1]/fieldset[1]/div[2]/table\
                               //*[@id="ant_ghs5s"]')
            miners_device['ghs5s'] = page.xpath('/html/body/div[3]/div[2]/div[1]/fieldset[1]/div[2]/table\
                                                 //*[@id="ant_ghs5s"]')[0].text
            ghsav = page.xpath('/html/body/div[3]/div[2]/div[1]/fieldset[1]/div[2]/table\
                               //*[@id="ant_ghsav"]')
            miners_device['ghsavg'] = page.xpath('/html/body/div[3]/div[2]/div[1]/fieldset[1]/div[2]/table\
                                                  //*[@id="ant_ghsav"]')[0].text
            chain_n = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-chain"]')
            asic = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-asic"]')
            rate = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-rate"]')
            nodes = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-status"]')
            t_PCB = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-temp"]')
            t_chip = page.xpath('//table[@id="ant_devs"]//*[@id="cbi-table-1-temp2"]')
            #miner_stat = []
            while chain_n:
                #miner_stat.append([chain_n.pop(0).text, asic.pop(0).text, rate.pop(0).text,
                #                   str(nodes.pop(0).text)])
                miners = {}
                miners['chain'] = chain_n.pop(0).text
                miners['MHS'] = rate.pop(0).text
                miners['ASIC'] = asic.pop(0).text
                miners['t_PCB'] = t_PCB.pop(0).text
                if miners['t_PCB']:
                    if str(miners['t_PCB']).find('I')>-1:
                        miners['t_PCB'] = re.findall(r'\d+$', miners['t_PCB'])[0]
                miners['t_chip'] = t_chip.pop(0).text
                if miners['t_chip']:
                    if str(miners['t_chip']).find('I')>-1:
                        miners['t_chip'] = re.findall(r'\d+$', miners['t_chip'])[0]
                tmp_nodes = str(nodes.pop(0).text).lower()
                miners['countX'] = int(tmp_nodes.count('x'))
                miners['countO'] = int(tmp_nodes.count('o'))
                miners['t_min'] = '-'
                miners['t_max'] = '-'
                miners['t_avg'] = '-'
                miners['status'] = '-'
                if miners['t_PCB']:
                    miners_device['miners'].append(miners)

            pool_url = page.xpath('//*[@id="ant_pools"]//*[@id="cbi-table-1-url"]')
            pool_user = page.xpath('//*[@id="ant_pools"]//*[@id="cbi-table-1-user"]')
            pool_status = page.xpath('//*[@id="ant_pools"]//*[@id="cbi-table-1-status"]')
            while pool_status:
                pools = {}
                pools['url'] = pool_url.pop(0).text
                if not pools['url']: pools['url'] = '-'
                pools['user'] = pool_user.pop(0).text
                if not pools['user']: pools['user'] = '-'
                #status may be Alive and Dead
                pools['status'] = pool_status.pop(0).text
                #if not pools['status']: pools['status'] = '-'
                if pools['status']:
                    miners_device['pools'].append(pools)

            fan_speed = page.xpath('//*[@id="ant_fans"]//*/td[@*]')
            while fan_speed:
                fan = {'speed':fan_speed.pop(0).text}
                if fan['speed']:
                    miners_device['fans'].append(fan)

    except requests.exceptions.ConnectionError:
        miners_device['type'] = miner
    except requests.exceptions.RequestException:
        miners_device['type'] = miner
    except requests.exceptions.ReadTimeoutError:
        miners_device['type'] = miner

    return miners_device


cursor.execute("""CREATE TABLE IF NOT EXISTS miners_device
                  (ip TEXT NOT NULL PRIMARY KEY,
                  status INTEGER NOT NULL DEFAULT 0,
                  type TEXT DEFAULT '-',
                  minertype TEXT,
                  login TEXT NOT NULL DEFAULT 'root',
                  password TEXT NOT NULL DEFAULT 'root',
                  name TEXT,
                  device_group INTEGER NOT NULL DEFAULT 0,
                  ghs5s TEXT,
                  ghsavg TEXT,
                  uptime TEXT DEFAULT '0d0h0m0s',
                  last_check INTEGER DEFAULT 0,
                  last_success_check INTEGER DEFAULT 0)
               """)

cursor.execute("""CREATE TABLE IF NOT EXISTS pools
                 (ip TEXT NOT NULL,
                 url TEXT,
                 user TEXT,
                 status TEXT)
               """)

cursor.execute("""CREATE TABLE IF NOT EXISTS asic
                 (ip TEXT NOT NULL,
                 chain TEXT,
                 MHS TEXT,
                 ASIC TEXT,
                 t_min TEXT NOT NULL DEFAULT '-',
                 t_max TEXT NOT NULL DEFAULT '-',
                 t_avg TEXT NOT NULL DEFAULT '-',
                 status TEXT NOT NULL DEFAULT '-',
                 t_PCB TEXT NOT NULL DEFAULT '-',
                 t_chip TEXT NOT NULL DEFAULT '-',
                 countX INTEGER NOT NULL DEFAULT 0,
                 countO INTEGER NOT NULL DEFAULT 0)
               """)

cursor.execute("""CREATE TABLE IF NOT EXISTS fans
                 (ip TEXT NOT NULL,
                 speed TEXT)
               """)

cursor.execute("""CREATE TABLE IF NOT EXISTS group_of_devices
                 (name TEXT NOT NULL UNIQUE)
               """)



cursor.execute('SELECT ip FROM miners_device')
res_sql = cursor.fetchall()
if res_sql:
    for row in res_sql:
        print("Check " + row[0])
        if is_available(row[0]):
            result = get_stat(row[0])
            if result['type'] > 0:
                cursor.execute('DELETE FROM fans WHERE ip="'+result['ip']+'"')
                for item in result['fans']:
                    cursor.execute('INSERT INTO fans(ip,speed) VALUES ("'+result['ip']+'","'+item['speed']+'")')
                cursor.execute('DELETE FROM asic WHERE ip="'+result['ip']+'"')
                for item in result['miners']:
                    cursor.execute('INSERT INTO asic(ip,chain,MHS,ASIC,t_min,t_max,t_avg,status,t_PCB,t_chip,countX,countO) \
                                    VALUES ("'+result['ip']+'","'+str(item['chain'])+'","'+item['MHS']+'","'+item['ASIC']+'",\
                                            "'+item['t_min']+'","'+item['t_max']+'","'+item['t_avg']+'","'+item['status']+'",\
                                            "'+item['t_PCB']+'","'+item['t_chip']+'",'+str(item['countX'])+','+str(item['countO'])+')')
                cursor.execute('DELETE FROM pools WHERE ip="'+result['ip']+'"')
                for item in result['pools']:
                    cursor.execute('INSERT INTO pools(ip,url,user,status) VALUES ("'+result['ip']+'","'+item['url']+'",\
                                                                                  "'+item['user']+'","'+item['status']+'")')
                cursor.execute('UPDATE miners_device SET status = "'+str(result['status'])+'", type = "'+str(result['type'])+'", \
                                minertype = "'+result['minertype']+'", name = "'+result['name']+'", ghs5s = "'+result['ghs5s']+'", \
                                ghsavg = "'+result['ghsavg']+'", uptime = "'+result['uptime']+'", last_check = datetime(), \
                                last_success_check = datetime() WHERE ip="'+result['ip']+'"')
            else:
                cursor.execute('UPDATE miners_device SET last_check=datetime(), name="NotSupportedDevice", minertype="NotSupportedDevice", \
                                status="'+str(result['status'])+'", type = "'+str(result['type'])+'" WHERE ip="'+row[0]+'"')
        else:
            cursor.execute('UPDATE miners_device SET last_check=datetime(), status=0 WHERE ip="'+row[0]+'"')
        conn.commit()



cursor.close()
conn.close()


